<script lang="ts" setup>
import BoardListController from '@/actions/App/Http/Controllers/BoardListController';
import ArchivedItemsSheet from '@/components/board/ArchivedItemsSheet.vue';
import BoardList from '@/components/board/board-list/BoardList.vue';
import BoardDropdownMenu from '@/components/board/BoardDropdownMenu.vue';
import BoardMembersDialog from '@/components/board/BoardMembersDialog.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import WorkspaceAvatar from '@/components/workspace/WorkspaceAvatar.vue';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import cardRoutes from '@/routes/board-lists/cards';
import boardRoutes from '@/routes/boards';
import boardListRoutes from '@/routes/boards/board-lists';
import { dashboard } from '@/routes';
import { home } from '@/routes/workspaces';
import { favorite } from '@/routes/workspaces/boards';
import type {
    ArchivedBoardList,
    Board,
    BoardList as BoardListType,
    BreadcrumbItem,
    Card,
    DeletedCard,
    SortableChangeEvent,
    WorkspaceMember,
} from '@/types';
import type { CancelToken, RequestPayload } from '@inertiajs/core';
import { Form, Head, Link, router, useHttp, usePage } from '@inertiajs/vue3';
import { onClickOutside, useMediaQuery } from '@vueuse/core';
import { Plus, Star, X } from 'lucide-vue-next';
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, provide, ref, useTemplateRef, watch } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';

// The card dialog carries the rich text editor, so it loads the first time a card opens.
const CardDialog = defineAsyncComponent(() => import('@/components/board/board-list/card/CardDialog.vue'));

const props = defineProps<{
    board: Board;
    selectedCard?: Card | null;
    colors: Array<string>;
    owner: WorkspaceMember;
    members: WorkspaceMember[];
    canManageMembers: boolean;
    /** Workspace members who aren't on the board yet. Only sent to the owner. */
    addableMembers?: WorkspaceMember[];
}>();

// The card description editor reads the list colours for its highlighter.
provide('colors', props.colors);

const page = usePage();
const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');

/*
|--------------------------------------------------------------------------
| Local board state
|--------------------------------------------------------------------------
| Lists and cards are edited optimistically on a local copy. Server
| snapshots replace it only when nothing is being dragged or saved,
| so a slow response can't snap a card back mid-move.
*/

function cloneLists(boardLists: BoardListType[]): BoardListType[] {
    return boardLists.map((list) => ({ ...list, cards: list.cards.map((card) => ({ ...card })) }));
}

const lists = ref<BoardListType[]>(cloneLists(props.board.board_lists));
const boardName = ref(props.board.name);
// What is being dragged: empty lists only invite a drop while a card is in flight, not a whole list.
const draggedItem = ref<'card' | 'list' | null>(null);
const isDragging = computed(() => draggedItem.value !== null);
let pendingRequests = 0;
let hasPendingSync = false;
let lastDragEndedAt = 0;

function applyServerBoard() {
    if (isDragging.value || pendingRequests > 0) {
        hasPendingSync = true;

        return;
    }

    hasPendingSync = false;
    lists.value = cloneLists(props.board.board_lists);

    if (!isEditingName.value) {
        boardName.value = props.board.name;
    }
}

watch(() => props.board, applyServerBoard);

/*
|--------------------------------------------------------------------------
| Workspace context
|--------------------------------------------------------------------------
*/

const workspace = computed(
    () =>
        [...page.props.ownedWorkspaces, ...page.props.sharedWorkspaces].find(
            ({ id }) => id === props.board.workspace_id,
        ) ?? null,
);
const workspaceName = computed(() => workspace.value?.name ?? 'Workspace');
const workspaceHomeUrl = computed(() => home(props.board.workspace_id).url);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: workspaceName.value, href: workspaceHomeUrl.value },
    { title: boardName.value, href: boardRoutes.show(props.board.id).url },
]);

const cardCount = computed(() => lists.value.reduce((total, list) => total + list.cards.length, 0));
const summary = computed(() => {
    const listLabel = `${lists.value.length} ${lists.value.length === 1 ? 'list' : 'lists'}`;

    return `${listLabel} · ${cardCount.value} ${cardCount.value === 1 ? 'card' : 'cards'}`;
});

/*
|--------------------------------------------------------------------------
| Screen reader announcements
|--------------------------------------------------------------------------
*/

const announcement = ref('');

async function announce(message: string) {
    announcement.value = '';
    await nextTick();
    announcement.value = message;
}

/*
|--------------------------------------------------------------------------
| Saving changes
|--------------------------------------------------------------------------
| Every change runs as an async visit so they never cancel each other,
| and only the board prop comes back. On any failure the board falls
| back to the last server snapshot and re-fetches it.
*/

let hasLostAccess = false;

/**
 * The board was deleted, or the user was taken off it or out of its workspace (maybe in another tab).
 * The dashboard opens the workspace home if they're still in the workspace, and their boards if not.
 */
function leaveInaccessibleBoard() {
    if (hasLostAccess) {
        return;
    }

    hasLostAccess = true;
    // The toast waits for the next page, since the board page's toasts go with it.
    router.visit(dashboard().url, {
        replace: true,
        onFinish: () =>
            toast.error('You no longer have access to this board', {
                description: 'It was deleted, or you were removed from it.',
            }),
    });
}

/**
 * A 404 means either one card or list is gone, or the whole board is out of reach.
 * Re-fetching the board tells which: losing access sends the user away, anything else is an ordinary failure.
 * The board's own address is asked for, not an open card's, since that card may be what's gone; the card closes.
 */
function confirmBoardAccess(onStillAccessible: () => void) {
    descriptionRequest?.cancel();
    router.visit(boardRoutes.show(props.board.id).url, {
        only: ['board', 'selectedCard'],
        async: true,
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: onStillAccessible,
        onHttpException: (response) => {
            if (response.status === 404) {
                leaveInaccessibleBoard();
            } else {
                onStillAccessible();
            }

            return false;
        },
        onNetworkError: () => {
            onStillAccessible();

            return false;
        },
    });
}

function rollback(message: string, status?: number) {
    lists.value = cloneLists(props.board.board_lists);
    boardName.value = props.board.name;

    if (status === 404) {
        confirmBoardAccess(() => toast.error(message));

        return;
    }

    toast.error(message);
    // The open card's description is reloaded too, in case it was the change that failed.
    router.reload({
        only: ['board', 'selectedCard'],
        async: true,
        onHttpException: () => false,
        onNetworkError: () => false,
    });
}

function onHttpFailure(response: { status: number }, showFailure: () => void) {
    if (response.status === 404) {
        confirmBoardAccess(showFailure);
    } else {
        showFailure();
    }
}

function send(
    method: 'patch' | 'delete',
    url: string,
    data: RequestPayload,
    failureMessage: string,
    onSuccess?: () => void,
    onFailure?: () => void,
) {
    pendingRequests++;

    router.visit(url, {
        method,
        data,
        async: true,
        replace: true,
        preserveScroll: true,
        preserveState: true,
        only: ['board'],
        onSuccess: () => onSuccess?.(),
        onError: (errors) => {
            rollback(Object.values(errors)[0] ?? failureMessage);
            onFailure?.();
        },
        onHttpException: (response) => {
            rollback(failureMessage, response.status);
            onFailure?.();

            return false;
        },
        onNetworkError: () => {
            rollback(`${failureMessage} Check your connection.`);
            onFailure?.();

            return false;
        },
        onFinish: () => {
            pendingRequests = Math.max(pendingRequests - 1, 0);

            if (hasPendingSync) {
                applyServerBoard();
            }
        },
    });
}

/*
|--------------------------------------------------------------------------
| Board name
|--------------------------------------------------------------------------
*/

const isEditingName = ref(false);
const draftName = ref(boardName.value);
const nameInput = useTemplateRef<HTMLInputElement>('board-name-input');
const nameButton = useTemplateRef<HTMLButtonElement>('board-name-button');

async function startEditingName() {
    draftName.value = boardName.value;
    isEditingName.value = true;
    await nextTick();
    nameInput.value?.select();
}

/** Keyboard saves and cancels hand focus back to the name; a blur leaves it where the user clicked. */
async function stopEditingName(shouldRestoreFocus: boolean) {
    isEditingName.value = false;

    if (shouldRestoreFocus) {
        await nextTick();
        nameButton.value?.focus();
    }
}

function saveName(shouldRestoreFocus = false) {
    if (!isEditingName.value) {
        return;
    }

    stopEditingName(shouldRestoreFocus);
    const name = draftName.value.trim();

    if (!name || name === boardName.value) {
        return;
    }

    boardName.value = name;
    send('patch', boardRoutes.update(props.board.id).url, { name }, 'Could not rename the board.');
}

/*
|--------------------------------------------------------------------------
| Star and archive
|--------------------------------------------------------------------------
*/

const starHttp = useHttp();
const isStarred = ref(false);
const isArchiving = ref(false);

watch(
    () => page.props.starredBoards,
    (starredBoards) => {
        if (!starHttp.processing) {
            isStarred.value = starredBoards.some(({ id }) => id === props.board.id);
        }
    },
    { immediate: true },
);

function toggleStar() {
    if (starHttp.processing) {
        return;
    }

    const shouldStar = !isStarred.value;
    isStarred.value = shouldStar;

    const undoStar = () => {
        isStarred.value = !shouldStar;
        toast.error('Could not update the star. Try again.');
    };

    starHttp
        .post(favorite({ workspace: props.board.workspace_id, board: props.board.id }).url, {
            // Refresh the sidebar's Starred section; if that fails it catches up on the next visit.
            onSuccess: () =>
                router.reload({
                    only: ['starredBoards'],
                    async: true,
                    onHttpException: () => false,
                    onNetworkError: () => false,
                }),
            onError: undoStar,
            onHttpException: (response) => onHttpFailure(response, undoStar),
            onNetworkError: undoStar,
        })
        .catch(() => {});
}

function archiveBoard() {
    if (isArchiving.value) {
        return;
    }

    isArchiving.value = true;
    const archivedName = boardName.value;

    const fail = () => {
        isArchiving.value = false;
        toast.error('Could not archive the board. Try again.');

        return false;
    };

    router.patch(
        boardRoutes.archive(props.board.id).url,
        {},
        {
            only: ['board'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () =>
                router.visit(workspaceHomeUrl.value, {
                    replace: true,
                    onSuccess: () =>
                        toast.success(`Archived “${archivedName}”`, {
                            description: 'You can restore it from Archived boards.',
                        }),
                }),
            onError: fail,
            onHttpException: (response) => {
                onHttpFailure(response, fail);

                return false;
            },
            onNetworkError: fail,
        },
    );
}

/*
|--------------------------------------------------------------------------
| Members
|--------------------------------------------------------------------------
*/

const MEMBER_AVATAR_CAP = 3;

const { getInitials } = useInitials();
const showMembersDialog = ref(false);
const boardPeople = computed(() => [props.owner, ...props.members]);
const hiddenPeopleCount = computed(() => Math.max(boardPeople.value.length - MEMBER_AVATAR_CAP, 0));
// Keep the bubble the size of an avatar however big the board gets; the dialog lists everyone.
const hiddenPeopleLabel = computed(() => (hiddenPeopleCount.value > 99 ? '99+' : `+${hiddenPeopleCount.value}`));
const peopleLabel = computed(() => {
    const count = boardPeople.value.length;

    return `${count} ${count === 1 ? 'person' : 'people'} on this board`;
});

/** A member change came back 404: the person was already gone, or the user lost the board itself. */
function onMemberNotFound(failureMessage: string) {
    confirmBoardAccess(() => {
        toast.error(failureMessage, { description: 'The member list changed. It has been refreshed.' });
        router.reload({
            only: ['members', 'addableMembers'],
            async: true,
            onHttpException: () => false,
            onNetworkError: () => false,
        });
    });
}

/*
|--------------------------------------------------------------------------
| Canvas: scrolling, mobile list navigation
|--------------------------------------------------------------------------
*/

const canvas = useTemplateRef<HTMLElement>('canvas');
const listNav = useTemplateRef<HTMLElement>('list-nav');
const activeListIndex = ref(0);
let scrollFrame = 0;

function columnElements() {
    return Array.from(canvas.value?.querySelectorAll<HTMLElement>('[data-list-id]') ?? []);
}

function updateActiveList() {
    scrollFrame = 0;
    const scroller = canvas.value;

    if (!scroller) {
        return;
    }

    const center = scroller.scrollLeft + scroller.clientWidth / 2;
    let closestIndex = 0;
    let closestDistance = Infinity;

    columnElements().forEach((column, index) => {
        const distance = Math.abs(column.offsetLeft + column.offsetWidth / 2 - center);

        if (distance < closestDistance) {
            closestDistance = distance;
            closestIndex = index;
        }
    });

    activeListIndex.value = closestIndex;
}

function onCanvasScroll() {
    if (!scrollFrame) {
        scrollFrame = requestAnimationFrame(updateActiveList);
    }
}

// Turn vertical wheel movement into horizontal scrolling, except over a list that scrolls itself.
function onCanvasWheel(event: WheelEvent) {
    const scroller = canvas.value;

    if (!scroller || event.ctrlKey || Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
        return;
    }

    const cardScroller = (event.target as HTMLElement).closest<HTMLElement>('[data-card-scroller]');

    if (cardScroller && cardScroller.scrollHeight > cardScroller.clientHeight) {
        return;
    }

    // Line-based wheels (mostly Firefox) report rows rather than pixels.
    const distance = event.deltaMode === WheelEvent.DOM_DELTA_LINE ? event.deltaY * WHEEL_LINE_HEIGHT : event.deltaY;

    // Trackpads already send a smooth stream of small steps, and phones snap list by list.
    const isTrackpad = event.deltaMode === WheelEvent.DOM_DELTA_PIXEL && Math.abs(distance) < 50;

    if (prefersReducedMotion.value || isTrackpad) {
        stopWheelScroll();
        scroller.scrollLeft += distance;

        return;
    }

    if (getComputedStyle(scroller).scrollSnapType !== 'none') {
        scroller.scrollBy({ left: distance, behavior: 'smooth' });

        return;
    }

    // Glide towards a target that each notch pushes further, so fast scrolling stays fluid.
    const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;
    wheelTarget = Math.min(Math.max((wheelTarget ?? scroller.scrollLeft) + distance, 0), maxScrollLeft);

    if (!wheelFrame) {
        wheelFrame = requestAnimationFrame(stepWheelScroll);
    }
}

const WHEEL_LINE_HEIGHT = 40;
const WHEEL_EASING = 0.18;
let wheelTarget: number | null = null;
let wheelFrame = 0;

function stepWheelScroll() {
    const scroller = canvas.value;

    if (!scroller || wheelTarget === null) {
        stopWheelScroll();

        return;
    }

    const remaining = wheelTarget - scroller.scrollLeft;

    if (Math.abs(remaining) < 1) {
        scroller.scrollLeft = wheelTarget;
        stopWheelScroll();

        return;
    }

    // Move at least a pixel per frame so rounding can't stall the glide.
    scroller.scrollLeft += Math.sign(remaining) * Math.max(Math.abs(remaining) * WHEEL_EASING, 1);
    wheelFrame = requestAnimationFrame(stepWheelScroll);
}

/** Hand control back to the user or to another scroll, such as jumping to a list. */
function stopWheelScroll() {
    cancelAnimationFrame(wheelFrame);
    wheelFrame = 0;
    wheelTarget = null;
}

function scrollBehavior(): ScrollBehavior {
    return prefersReducedMotion.value ? 'auto' : 'smooth';
}

function jumpToList(index: number) {
    stopWheelScroll();
    columnElements()[index]?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'center' });
}

function scrollListIntoView(listId: string) {
    stopWheelScroll();
    columnElements()
        .find((column) => column.dataset.listId === listId)
        ?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'nearest' });
}

watch(activeListIndex, (index) => {
    listNav.value
        ?.querySelectorAll('button')
        [index]?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'nearest' });
});

onBeforeUnmount(() => {
    cancelAnimationFrame(scrollFrame);
    stopWheelScroll();
});

/*
|--------------------------------------------------------------------------
| Drag and drop
|--------------------------------------------------------------------------
| Touch drags start after a short press so swiping still scrolls the
| board. The fallback renderer keeps mouse and touch drags identical
| and lets SortableJS auto-scroll the board and lists near their edges.
*/

const sharedDragOptions = computed(() => ({
    animation: prefersReducedMotion.value ? 0 : 180,
    forceFallback: true,
    fallbackOnBody: true,
    fallbackTolerance: 4,
    delay: 200,
    delayOnTouchOnly: true,
    touchStartThreshold: 6,
    ghostClass: 'board-drag-ghost',
    dragClass: 'board-drag-active',
    scroll: true,
    bubbleScroll: true,
    scrollSensitivity: 80,
    scrollSpeed: 16,
    filter: 'input, textarea, select, [contenteditable]',
    preventOnFilter: false,
}));

const listDragOptions = computed(() => ({
    ...sharedDragOptions.value,
    group: 'board-lists',
    handle: '[data-list-handle]',
    draggable: '.board-column',
}));

const cardDragOptions = computed(() => ({
    ...sharedDragOptions.value,
    group: 'board-cards',
    draggable: '.board-card',
    emptyInsertThreshold: 24,
}));

function onDragStart(item: 'card' | 'list') {
    draggedItem.value = item;
}

function onDragEnd() {
    draggedItem.value = null;
    lastDragEndedAt = Date.now();

    if (hasPendingSync) {
        applyServerBoard();
    }
}

/*
|--------------------------------------------------------------------------
| Lists
|--------------------------------------------------------------------------
*/

function persistListOrder() {
    send(
        'patch',
        boardListRoutes.reorder(props.board.id).url,
        { boardLists: lists.value.map((list, index) => ({ id: list.id, order: index })) },
        'Could not save the list order.',
    );
}

function onListsChange(event: SortableChangeEvent<BoardListType>) {
    if (!event.moved) {
        return;
    }

    persistListOrder();
    announce(
        `Moved list ${event.moved.element.name} to position ${event.moved.newIndex + 1} of ${lists.value.length}.`,
    );
}

async function moveList(list: BoardListType, direction: -1 | 1) {
    const from = lists.value.indexOf(list);
    const to = from + direction;

    if (from === -1 || to < 0 || to >= lists.value.length) {
        return;
    }

    // Remember where every column sits, so they can glide from there to their new places.
    const previousLefts = new Map(
        columnElements().map((column) => [column.dataset.listId, column.getBoundingClientRect().left]),
    );

    lists.value.splice(to, 0, ...lists.value.splice(from, 1));
    persistListOrder();
    announce(`Moved list ${list.name} to position ${to + 1} of ${lists.value.length}.`);

    await nextTick();
    animateColumnsFrom(previousLefts, list.id);
    scrollListIntoView(list.id);
}

const LIST_MOVE_DURATION_MS = 280;

/** FLIP: play each column from its old position to its new one, with the moved list on top. */
function animateColumnsFrom(previousLefts: Map<string | undefined, number>, movedListId: string) {
    if (prefersReducedMotion.value) {
        return;
    }

    for (const column of columnElements()) {
        const previousLeft = previousLefts.get(column.dataset.listId);
        const offset = previousLeft === undefined ? 0 : previousLeft - column.getBoundingClientRect().left;

        if (!offset) {
            continue;
        }

        const isMovedList = column.dataset.listId === movedListId;

        if (isMovedList) {
            column.style.zIndex = '10';
        }

        column
            .animate(
                isMovedList
                    ? [
                          { transform: `translateX(${offset}px)` },
                          { transform: `translateX(${offset / 2}px) translateY(-6px) scale(1.02)`, offset: 0.5 },
                          { transform: 'none' },
                      ]
                    : [{ transform: `translateX(${offset}px)` }, { transform: 'none' }],
                { duration: LIST_MOVE_DURATION_MS, easing: 'cubic-bezier(0.2, 0, 0, 1)' },
            )
            .finished.catch(() => {})
            .finally(() => {
                if (isMovedList) {
                    column.style.zIndex = '';
                }
            });
    }
}

function updateListUrl(list: BoardListType) {
    return boardListRoutes.update({ board: props.board.id, board_list: list.id }).url;
}

function listRouteArgs(list: Pick<BoardListType, 'id'>) {
    return { board: props.board.id, board_list: list.id };
}

function renameList(list: BoardListType, name: string) {
    list.name = name;
    send('patch', updateListUrl(list), { name }, 'Could not rename the list.');
}

function recolorList(list: BoardListType, color: string | null) {
    list.color = color;
    send('patch', updateListUrl(list), { color }, 'Could not change the list colour.');
}

function archiveList(list: BoardListType) {
    const index = lists.value.indexOf(list);

    if (index === -1) {
        return;
    }

    lists.value.splice(index, 1);
    send('patch', boardListRoutes.archive(listRouteArgs(list)).url, {}, 'Could not archive the list.');

    toast(`Archived “${list.name}”`, {
        description: 'You can restore it from Archived items.',
        action: { label: 'Undo', onClick: () => restoreList(list, index) },
    });
}

function restoreList(list: BoardListType, index: number) {
    if (lists.value.some(({ id }) => id === list.id)) {
        return;
    }

    lists.value.splice(Math.min(index, lists.value.length), 0, list);
    send('patch', boardListRoutes.unarchive(listRouteArgs(list)).url, {}, 'Could not restore the list.');
    announce(`Restored list ${list.name}.`);
}

/*
|--------------------------------------------------------------------------
| Archived items
|--------------------------------------------------------------------------
| Archived lists and deleted cards come back from the sheet. They take
| their old places on the board when the server sends it back; a failed
| restore reloads the sheet so it shows what is still archived.
*/

const showArchivedItems = ref(false);
const archivedItemsSheet = useTemplateRef<InstanceType<typeof ArchivedItemsSheet>>('archived-items-sheet');

function reloadArchivedItems() {
    archivedItemsSheet.value?.reload();
}

function restoreArchivedList(list: ArchivedBoardList) {
    send(
        'patch',
        boardListRoutes.unarchive(listRouteArgs(list)).url,
        {},
        `Could not restore “${list.name}”.`,
        undefined,
        reloadArchivedItems,
    );
}

function restoreDeletedCard(card: DeletedCard) {
    send(
        'patch',
        cardRoutes.restore({ board_list: card.board_list_id, card: card.id }).url,
        {},
        `Could not restore “${card.name}”.`,
        undefined,
        reloadArchivedItems,
    );
}

const isAddingList = ref(lists.value.length === 0);
const listInput = useTemplateRef<HTMLInputElement>('list-input');
const listComposer = useTemplateRef<HTMLElement>('list-composer');

async function openListComposer() {
    isAddingList.value = true;
    await nextTick();
    listInput.value?.focus({ preventScroll: true });
    stopWheelScroll();
    canvas.value?.scrollTo({ left: canvas.value.scrollWidth, behavior: scrollBehavior() });
}

function closeListComposer() {
    isAddingList.value = false;
}

// Clicking anywhere else on the page puts the composer away, like pressing Escape.
onClickOutside(listComposer, closeListComposer);

async function onListAdded() {
    announce('List added.');
    await nextTick();
    stopWheelScroll();
    canvas.value?.scrollTo({ left: canvas.value.scrollWidth, behavior: scrollBehavior() });
    listInput.value?.focus({ preventScroll: true });
}

function onFormRequestFailed() {
    toast.error('Could not save. Check your connection and try again.');

    return false;
}

function onFormHttpException(response: { status: number }) {
    onHttpFailure(response, onFormRequestFailed);

    return false;
}

function onCardRequestFailed(message: string, status?: number) {
    if (status === 404) {
        confirmBoardAccess(() => toast.error(message));
    } else {
        toast.error(message);
    }
}

/*
|--------------------------------------------------------------------------
| Cards
|--------------------------------------------------------------------------
| The open card lives in the URL so it can be shared, but opening and
| closing it are client-side visits: the board already has the card.
| Only its description is fetched, since the board leaves those out.
*/

const activeCard = computed<Card | null>(() => {
    const id = props.selectedCard?.id;

    if (!id) {
        return null;
    }

    for (const list of lists.value) {
        const card = list.cards.find((candidate) => candidate.id === id);

        if (card) {
            return card;
        }
    }

    return props.selectedCard ?? null;
});

// Undefined until the open card's description has loaded.
const activeCardDescription = computed(() => props.selectedCard?.description);

// Stays true after the first card opens so the dialog can animate closed.
const hasOpenedCard = ref(!!props.selectedCard);

watch(activeCard, (card) => {
    if (card) {
        hasOpenedCard.value = true;
    }
});

const pageTitle = computed(() =>
    activeCard.value ? `${activeCard.value.name} · ${boardName.value}` : boardName.value,
);

function cardUrl(card: Pick<Card, 'id' | 'board_list_id'>) {
    return cardRoutes.show({ board_list: card.board_list_id, card: card.id }).url;
}

function showCard(card: Card | null, url: string, onFinish?: () => void) {
    // A card that stays open keeps the description it already loaded.
    const description = card && card.id === props.selectedCard?.id ? props.selectedCard.description : undefined;

    router.replace({
        url,
        props: (currentProps) => ({ ...currentProps, selectedCard: card ? { ...card, description } : null }),
        preserveScroll: true,
        preserveState: true,
        onFinish,
    });
}

let descriptionRequest: CancelToken | null = null;

/** Fetch the open card's description. A failure closes the card, since it can't be edited without it. */
function loadActiveCardDescription() {
    let request: CancelToken | null = null;

    router.reload({
        only: ['selectedCard'],
        async: true,
        onCancelToken: (token) => (request = descriptionRequest = token),
        onHttpException: (response) => {
            closeCard(() => onCardRequestFailed('Could not open the card.', response.status));

            return false;
        },
        onNetworkError: () => {
            closeCard(() => toast.error('Could not open the card. Check your connection.'));

            return false;
        },
        onFinish: () => {
            if (descriptionRequest === request) {
                descriptionRequest = null;
            }
        },
    });
}

function openCard(card: Card) {
    // A drop can fire a click on the card that was just dragged.
    if (isDragging.value || Date.now() - lastDragEndedAt < 250) {
        return;
    }

    descriptionRequest?.cancel();
    showCard(card, cardUrl(card), () => {
        if (activeCardDescription.value === undefined) {
            loadActiveCardDescription();
        }
    });
}

function closeCard(onFinish?: () => void) {
    // A description still loading would reopen the card when it arrives.
    descriptionRequest?.cancel();
    showCard(null, boardRoutes.show(props.board.id).url, onFinish);
}

function persistCardOrder(list: BoardListType) {
    list.cards.forEach((card, index) => (card.order = index));

    send(
        'patch',
        cardRoutes.reorder(list.id).url,
        { cards: list.cards.map(({ id, order }) => ({ id, order })) },
        'Could not save the card order.',
    );
}

function onCardsChange(list: BoardListType, event: SortableChangeEvent<Card>) {
    if (event.moved) {
        persistCardOrder(list);
        announce(`Moved card ${event.moved.element.name} to position ${event.moved.newIndex + 1} in ${list.name}.`);
    }

    if (event.added) {
        const card = event.added.element;
        const fromListId = card.board_list_id;
        card.board_list_id = list.id;

        send(
            'patch',
            cardRoutes.update({ board_list: fromListId, card: card.id }).url,
            { board_list_id: list.id, order: event.added.newIndex },
            'Could not move the card.',
        );
        persistCardOrder(list);
        announce(`Moved card ${card.name} to ${list.name}.`);
    }
}

/** Move a card within its list from the card's menu, the keyboard way to drag it. */
async function reorderCard(card: Card, to: number) {
    const list = lists.value.find(({ id }) => id === card.board_list_id);
    const from = list?.cards.findIndex(({ id }) => id === card.id) ?? -1;

    if (!list || from === -1 || from === to || to < 0 || to >= list.cards.length) {
        return;
    }

    list.cards.splice(to, 0, ...list.cards.splice(from, 1));
    persistCardOrder(list);
    announce(`Moved card ${card.name} to position ${to + 1} of ${list.cards.length} in ${list.name}.`);

    // Keep the card in sight behind the dialog, so it's where the user expects once the dialog closes.
    await nextTick();
    canvas.value
        ?.querySelector(`[data-card-id="${card.id}"]`)
        ?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'nearest' });
}

function renameCard(card: Card, name: string) {
    card.name = name;
    send(
        'patch',
        cardRoutes.update({ board_list: card.board_list_id, card: card.id }).url,
        { name },
        'Could not rename the card.',
    );
}

function describeCard(card: Card, description: string) {
    card.has_description = description.replace(/<[^>]*>/g, '').trim() !== '';
    router.replace({
        props: (currentProps) => {
            const selectedCard = currentProps.selectedCard as Card | null | undefined;

            return {
                ...currentProps,
                selectedCard: selectedCard?.id === card.id ? { ...selectedCard, description } : selectedCard,
            };
        },
        preserveScroll: true,
        preserveState: true,
    });
    send(
        'patch',
        cardRoutes.update({ board_list: card.board_list_id, card: card.id }).url,
        { description },
        'Could not save the description.',
    );
}

function moveCard(card: Card, boardListId: string) {
    const fromList = lists.value.find(({ id }) => id === card.board_list_id);
    const toList = lists.value.find(({ id }) => id === boardListId);

    if (!fromList || !toList) {
        return;
    }

    const order = toList.cards.reduce((highest, { order }) => Math.max(highest, order), -1) + 1;

    fromList.cards = fromList.cards.filter(({ id }) => id !== card.id);
    card.board_list_id = toList.id;
    card.order = order;
    toList.cards.push(card);
    announce(`Moved card ${card.name} to ${toList.name}.`);

    // A description still loading is asked for at the old address, so it is fetched again once the card has moved.
    descriptionRequest?.cancel();

    // Point the URL at the card's new list first: the server redirects back to it after saving.
    showCard(card, cardUrl(card), () =>
        send(
            'patch',
            cardRoutes.update({ board_list: fromList.id, card: card.id }).url,
            { board_list_id: toList.id, order },
            'Could not move the card.',
            () => {
                if (activeCard.value?.id === card.id && activeCardDescription.value === undefined) {
                    loadActiveCardDescription();
                }
            },
        ),
    );
}

function deleteCard(card: Card) {
    const list = lists.value.find(({ id }) => id === card.board_list_id);
    const index = list?.cards.indexOf(card) ?? -1;

    if (list) {
        list.cards = list.cards.filter(({ id }) => id !== card.id);
    }

    // Leave the card's URL before deleting it, or the redirect back would land on a missing card.
    closeCard(() =>
        send(
            'delete',
            cardRoutes.destroy({ board_list: card.board_list_id, card: card.id }).url,
            {},
            'Could not delete the card.',
            () =>
                toast(`Deleted “${card.name}”`, {
                    description: 'You can restore it from Archived items.',
                    action: { label: 'Undo', onClick: () => restoreCard(card, index) },
                }),
        ),
    );
}

/** Put a deleted card back where it was. If its list was archived meanwhile, it returns with that list. */
function restoreCard(card: Card, index: number) {
    const list = lists.value.find(({ id }) => id === card.board_list_id);

    if (list?.cards.some(({ id }) => id === card.id)) {
        return;
    }

    list?.cards.splice(index === -1 ? list.cards.length : Math.min(index, list.cards.length), 0, card);
    send(
        'patch',
        cardRoutes.restore({ board_list: card.board_list_id, card: card.id }).url,
        {},
        'Could not restore the card.',
    );
    announce(`Restored card ${card.name}.`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" content-class="h-dvh md:h-[calc(100dvh-1rem)]">
        <Head :title="pageTitle" />

        <div class="flex min-h-0 flex-1 flex-col">
            <header class="flex items-center gap-3 border-b px-4 py-3 sm:px-6">
                <Link
                    :aria-label="`${workspaceName} home`"
                    :href="workspaceHomeUrl"
                    class="shrink-0 rounded-lg outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <WorkspaceAvatar
                        :workspace="workspace ?? { name: workspaceName }"
                        class="size-9 rounded-lg sm:size-11 sm:rounded-xl"
                    />
                </Link>

                <div class="min-w-0 flex-1">
                    <Link
                        :href="workspaceHomeUrl"
                        class="block max-w-full truncate text-xs font-medium text-muted-foreground transition-colors outline-none hover:text-foreground focus-visible:underline sm:text-sm"
                    >
                        {{ workspaceName }}
                    </Link>
                    <h1 v-if="!isEditingName" class="flex min-w-0">
                        <button
                            class="-mx-1.5 max-w-full cursor-pointer truncate rounded-md px-1.5 text-left text-lg leading-tight font-semibold tracking-tight transition-colors outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:text-2xl"
                            title="Rename board"
                            type="button"
                            ref="board-name-button"
                            @click="startEditingName"
                        >
                            {{ boardName }}
                        </button>
                    </h1>
                    <div v-else class="-mx-1.5 inline-grid max-w-full grid-cols-1">
                        <span
                            aria-hidden="true"
                            class="invisible col-start-1 row-start-1 overflow-hidden px-1.5 text-lg leading-tight font-semibold tracking-tight whitespace-pre sm:text-2xl"
                            >{{ draftName || ' ' }}</span
                        >
                        <input
                            ref="board-name-input"
                            v-model="draftName"
                            aria-label="Board name"
                            class="col-start-1 row-start-1 w-full min-w-24 rounded-md border border-ring bg-background px-1.5 text-lg leading-tight font-semibold tracking-tight ring-[3px] ring-ring/50 outline-none sm:text-2xl"
                            maxlength="255"
                            @blur="saveName()"
                            @keydown.enter.prevent="saveName(true)"
                            @keydown.esc.prevent="stopEditingName(true)"
                        />
                    </div>
                </div>

                <p class="hidden shrink-0 text-sm text-muted-foreground tabular-nums lg:block">{{ summary }}</p>

                <div class="flex shrink-0 items-center gap-1">
                    <button
                        :aria-label="`Board members: ${peopleLabel}`"
                        class="mr-1 hidden min-h-9 cursor-pointer items-center rounded-full p-0.5 transition-colors outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:flex"
                        type="button"
                        @click="showMembersDialog = true"
                    >
                        <span aria-hidden="true" class="flex -space-x-2">
                            <Avatar
                                v-for="person in boardPeople.slice(0, MEMBER_AVATAR_CAP)"
                                :key="person.id"
                                class="size-7 ring-2 ring-background sm:size-8"
                            >
                                <AvatarImage :src="person.avatar ?? ''" alt="" class="object-cover" />
                                <AvatarFallback class="bg-blush text-xs font-semibold text-blush-foreground">
                                    {{ getInitials(person.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                v-if="hiddenPeopleCount"
                                class="relative flex size-7 items-center justify-center rounded-full bg-muted text-[10px] font-semibold text-muted-foreground tabular-nums ring-2 ring-background sm:size-8 sm:text-xs"
                            >
                                {{ hiddenPeopleLabel }}
                            </span>
                        </span>
                    </button>
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    :aria-label="isStarred ? 'Unstar board' : 'Star board'"
                                    :aria-pressed="isStarred"
                                    class="cursor-pointer"
                                    size="icon"
                                    variant="ghost"
                                    @click="toggleStar"
                                >
                                    <Star
                                        :class="
                                            cn(
                                                'transition-colors',
                                                isStarred
                                                    ? 'fill-current text-primary'
                                                    : 'fill-transparent text-muted-foreground',
                                            )
                                        "
                                    />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{{ isStarred ? 'Unstar' : 'Star' }} this board</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <Button class="hidden cursor-pointer sm:inline-flex" variant="outline" @click="openListComposer">
                        <Plus />
                        Add list
                    </Button>
                    <BoardDropdownMenu
                        :is-archiving="isArchiving"
                        @add-list="openListComposer"
                        @archive-board="archiveBoard"
                        @show-archived-items="showArchivedItems = true"
                        @show-members="showMembersDialog = true"
                    />
                </div>
            </header>

            <nav v-if="lists.length > 1" aria-label="Jump to list" class="border-b sm:hidden">
                <ul ref="list-nav" class="flex [scrollbar-width:none] gap-1.5 overflow-x-auto px-4 py-2">
                    <li v-for="(list, index) in lists" :key="list.id" class="shrink-0">
                        <button
                            :aria-current="index === activeListIndex ? 'true' : undefined"
                            :class="
                                cn(
                                    'flex h-8 cursor-pointer items-center gap-1.5 rounded-full border px-3 text-sm transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                    index === activeListIndex
                                        ? 'border-primary/40 bg-blush font-medium text-blush-foreground'
                                        : 'border-border bg-background text-muted-foreground',
                                )
                            "
                            type="button"
                            @click="jumpToList(index)"
                        >
                            <span
                                :class="list.color ? `list-${list.color}` : 'list-default'"
                                aria-hidden="true"
                                class="size-2.5 rounded-full ring-1 ring-black/10 [background:var(--list-bg)] dark:ring-white/15"
                            />
                            <span class="max-w-36 truncate">{{ list.name }}</span>
                            <span class="text-xs tabular-nums opacity-70">{{ list.cards.length }}</span>
                        </button>
                    </li>
                </ul>
            </nav>

            <div
                ref="canvas"
                :class="isDragging ? 'snap-none' : 'snap-x snap-mandatory sm:snap-none'"
                class="board-canvas relative min-h-0 flex-1 scroll-px-4 overflow-x-auto overflow-y-hidden overscroll-x-contain"
                @scroll.passive="onCanvasScroll"
                @pointerdown="stopWheelScroll"
                @wheel.passive="onCanvasWheel"
            >
                <draggable
                    :list="lists"
                    class="flex h-full w-max items-start gap-3 p-4 sm:gap-4 sm:px-6"
                    item-key="id"
                    tag="ol"
                    v-bind="listDragOptions"
                    @change="onListsChange"
                    @end="onDragEnd"
                    @start="onDragStart('list')"
                >
                    <template #item="{ element, index }">
                        <li
                            :data-list-id="element.id"
                            class="board-column relative flex max-h-full w-[calc(100vw-3rem)] max-w-80 shrink-0 snap-center list-none flex-col self-start sm:w-72"
                        >
                            <BoardList
                                :board-list="element"
                                :can-move-left="index > 0"
                                :can-move-right="index < lists.length - 1"
                                :card-drag-options="cardDragOptions"
                                :colors="colors"
                                :is-dragging-card="draggedItem === 'card'"
                                @archive="archiveList(element)"
                                @cards-change="onCardsChange"
                                @drag-end="onDragEnd"
                                @drag-start="onDragStart('card')"
                                @move="moveList(element, $event)"
                                @open-card="openCard"
                                @recolor="recolorList(element, $event)"
                                @rename="renameList(element, $event)"
                                @request-failed="onCardRequestFailed"
                            />
                        </li>
                    </template>

                    <template #footer>
                        <li class="w-[calc(100vw-3rem)] max-w-80 shrink-0 snap-center sm:w-72">
                            <div
                                v-if="isAddingList"
                                ref="list-composer"
                                class="rounded-2xl border border-primary/25 bg-card p-3 shadow-sm ring-1 ring-primary/10 dark:border-primary/30 dark:ring-primary/15"
                            >
                                <p v-if="!lists.length" class="mb-3 text-sm text-muted-foreground">
                                    Lists are the columns of your board, like
                                    <span class="font-medium text-foreground">To do</span>,
                                    <span class="font-medium text-foreground">Doing</span> and
                                    <span class="font-medium text-foreground">Done</span>.
                                </p>
                                <Form
                                    v-slot="{ errors, processing }"
                                    :options="{ preserveScroll: true, preserveState: true, only: ['board'] }"
                                    class="space-y-2"
                                    reset-on-success
                                    v-bind="BoardListController.store.form(board.id)"
                                    @http-exception="onFormHttpException"
                                    @network-error="onFormRequestFailed"
                                    @success="onListAdded"
                                >
                                    <input
                                        ref="list-input"
                                        :aria-invalid="!!errors.name"
                                        aria-label="List name"
                                        autocomplete="off"
                                        class="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive sm:text-sm dark:bg-input/30"
                                        maxlength="255"
                                        name="name"
                                        placeholder="Enter list name"
                                        required
                                        @keydown.esc.prevent="closeListComposer"
                                    />
                                    <InputError :message="errors.name" />
                                    <div class="flex items-center gap-1.5">
                                        <Button :disabled="processing" class="cursor-pointer" size="sm" type="submit">
                                            {{ processing ? 'Adding…' : 'Add list' }}
                                        </Button>
                                        <Button
                                            aria-label="Stop adding lists"
                                            class="size-8 cursor-pointer"
                                            size="icon"
                                            type="button"
                                            variant="ghost"
                                            @click="closeListComposer"
                                        >
                                            <X />
                                        </Button>
                                    </div>
                                </Form>
                            </div>
                            <button
                                v-else
                                class="group flex h-12 w-full cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-primary/40 bg-card/80 px-3 text-sm font-medium text-foreground/80 shadow-xs backdrop-blur-sm transition-colors outline-none hover:border-primary/70 hover:bg-blush hover:text-blush-foreground focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:border-primary/50 dark:bg-card/60 dark:text-foreground/85 dark:hover:border-primary/80"
                                type="button"
                                @click="openListComposer"
                            >
                                <span
                                    aria-hidden="true"
                                    class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary/15 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
                                >
                                    <Plus class="size-4" />
                                </span>
                                {{ lists.length ? 'Add another list' : 'Add a list' }}
                            </button>
                        </li>
                    </template>
                </draggable>
            </div>
        </div>

        <CardDialog
            v-if="hasOpenedCard"
            :board-lists="lists"
            :board-name="boardName"
            :card="activeCard"
            :description="activeCardDescription"
            @close="closeCard()"
            @delete="deleteCard"
            @describe="describeCard"
            @move="moveCard"
            @rename="renameCard"
            @reorder="reorderCard"
        />

        <BoardMembersDialog
            v-model:open="showMembersDialog"
            :addable-members="addableMembers"
            :board-id="board.id"
            :board-name="boardName"
            :can-manage-members="canManageMembers"
            :members="members"
            :owner="owner"
            :workspace-name="workspaceName"
            @not-found="onMemberNotFound"
        />

        <ArchivedItemsSheet
            ref="archived-items-sheet"
            v-model:open="showArchivedItems"
            :board-id="board.id"
            :board-name="boardName"
            @restore-card="restoreDeletedCard"
            @restore-list="restoreArchivedList"
        />

        <p aria-live="polite" class="sr-only" role="status">{{ announcement }}</p>
    </AppLayout>
</template>
