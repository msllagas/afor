<script lang="ts" setup>
import ArchivedItemsSheet from '@/components/board/ArchivedItemsSheet.vue';
import BoardList from '@/components/board/board-list/BoardList.vue';
import BoardHeader from '@/components/board/BoardHeader.vue';
import BoardMembersDialog from '@/components/board/BoardMembersDialog.vue';
import LeaveBoardDialog from '@/components/board/LeaveBoardDialog.vue';
import ListComposer from '@/components/board/ListComposer.vue';
import { Button } from '@/components/ui/button';
import { useBoardStar } from '@/composables/useBoardStar';
import { useBoardSync } from '@/composables/useBoardSync';
import { useCanvasScroll } from '@/composables/useCanvasScroll';
import AppLayout from '@/layouts/AppLayout.vue';
import { reloadQuietly } from '@/lib/reloadQuietly';
import { cn } from '@/lib/utils';
import cardRoutes from '@/routes/board-lists/cards';
import boardRoutes from '@/routes/boards';
import boardListRoutes from '@/routes/boards/board-lists';
import { home } from '@/routes/workspaces';
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
import type { CancelToken } from '@inertiajs/core';
import { Head, router, useHttp, usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { ArchiveRestore, LogOut, Trash2 } from 'lucide-vue-next';
import { computed, defineAsyncComponent, nextTick, provide, ref, useTemplateRef, watch } from 'vue';
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
    /** Only the workspace owner can archive a board, restore it or delete it. */
    canArchive: boolean;
    canUnarchive: boolean;
    canDelete: boolean;
    /** Board members can leave, even an archived board; the owner is on every board. */
    canLeave: boolean;
    /** Workspace members who aren't on the board yet. Only sent to the owner. */
    addableMembers?: WorkspaceMember[];
}>();

// The card description editor reads the list colours for its highlighter.
provide('colors', props.colors);

const page = usePage();
const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');

const boardHeader = useTemplateRef<InstanceType<typeof BoardHeader>>('board-header');

// An archived board can be read, but nothing on it changes until it is restored. The server refuses changes too.
const isReadOnly = computed(() => !!props.board.archived_at);

const {
    lists,
    boardName,
    draggedItem,
    isDragging,
    isDraggingOrJustDropped,
    onDragStart,
    onDragEnd,
    send,
    onHttpFailure,
    confirmBoardAccess,
} = useBoardSync({
    board: () => props.board,
    isEditingName: () => boardHeader.value?.isEditingName ?? false,
    cancelDescriptionRequest: () => descriptionRequest?.cancel(),
    onArchiveChange: announceArchiveChange,
    openCardId: () => props.selectedCard?.id ?? null,
    onOpenCardMoved: followOpenCard,
});

function announceArchiveChange(isArchived: boolean) {
    if (isArchiving.value || isRestoring.value) {
        return;
    }

    if (isArchived) {
        toast('This board was archived', {
            description: "Nothing can be changed until it's restored, so anything you were still typing wasn't saved.",
        });
        announce('This board was archived. It is read-only now.');
    } else {
        toast('This board was restored', { description: 'You can edit it again.' });
        announce('This board was restored. You can edit it again.');
    }
}

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
| Board name
|--------------------------------------------------------------------------
*/

function renameBoard(name: string) {
    if (isReadOnly.value) {
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

const { isStarring, toggleStar: toggleBoardStar } = useBoardStar();
const isStarred = ref(false);
const isArchiving = ref(false);

watch(
    () => page.props.starredBoards,
    (starredBoards) => {
        if (!isStarring.value) {
            isStarred.value = starredBoards.some(({ id }) => id === props.board.id);
        }
    },
    { immediate: true },
);

function toggleStar() {
    if (isReadOnly.value || isStarring.value) {
        return;
    }

    const shouldStar = !isStarred.value;
    isStarred.value = shouldStar;
    toggleBoardStar(props.board, () => (isStarred.value = !shouldStar), onHttpFailure);
}

function archiveBoard() {
    if (!props.canArchive || isReadOnly.value || isArchiving.value) {
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
| Archived board: restore or delete
|--------------------------------------------------------------------------
| Only the workspace owner gets these; members see the board read-only.
*/

const restoreRequest = useHttp();
const deleteRequest = useHttp();
const isRestoring = ref(false);
const isDeletingBoard = ref(false);
const isConfirmingBoardDelete = ref(false);
const cancelBoardDeleteButton = useTemplateRef<{ $el: HTMLElement }>('cancel-board-delete-button');
const deleteBoardButton = useTemplateRef<{ $el: HTMLElement }>('delete-board-button');

const boardContents = computed(() => {
    if (!lists.value.length) {
        return 'It has no lists.';
    }

    const listLabel = `${lists.value.length} ${lists.value.length === 1 ? 'list' : 'lists'}`;

    return `Its ${listLabel} and ${cardCount.value} ${cardCount.value === 1 ? 'card' : 'cards'} go with it.`;
});

function failArchivedBoardAction(action: 'restore' | 'delete', status?: number, message?: string) {
    isRestoring.value = false;
    isDeletingBoard.value = false;

    const title =
        action === 'restore' ? `Couldn’t restore “${boardName.value}”` : `Couldn’t delete “${boardName.value}”`;

    if (status === 404) {
        confirmBoardAccess(() => toast.error(title));

        return;
    }

    const fallbackMessages: Record<number, string> = {
        403: 'Only the workspace owner can restore or delete this board.',
        419: 'Your session expired. Refresh the page, then try again.',
    };

    toast.error(title, {
        description:
            message ??
            (status === undefined
                ? 'Check your connection, then try again.'
                : (fallbackMessages[status] ?? 'Something went wrong on our end. Try again in a moment.')),
        action: { label: 'Try again', onClick: () => (action === 'restore' ? restoreBoard() : deleteBoard()) },
    });
}

function restoreBoard() {
    if (!props.canUnarchive || !isReadOnly.value || isRestoring.value) {
        return;
    }

    isRestoring.value = true;
    const restoredName = boardName.value;
    const showRestored = (description: string) => toast.success(`Restored “${restoredName}”`, { description });

    restoreRequest
        .patch(boardRoutes.unarchive(props.board.id).url, {
            // Bring back the editable board, its star and the members who can be added again.
            onSuccess: () =>
                router.reload({
                    only: ['board', 'starredBoards', 'canManageMembers', 'canArchive', 'addableMembers'],
                    onSuccess: () => {
                        showRestored('You can make changes to it again.');
                        announce(`Restored board ${restoredName}.`);
                    },
                    onHttpException: () => {
                        showRestored('Refresh the page to make changes to it.');

                        return false;
                    },
                    onNetworkError: () => {
                        showRestored('Refresh the page to make changes to it.');

                        return false;
                    },
                    onFinish: () => (isRestoring.value = false),
                }),
            onHttpException: (response) => failArchivedBoardAction('restore', response.status),
            onNetworkError: () => failArchivedBoardAction('restore'),
        })
        .catch(() => {});
}

async function askToDeleteBoard() {
    if (!props.canDelete) {
        return;
    }

    isConfirmingBoardDelete.value = true;
    await nextTick();
    // Start on the safe choice.
    cancelBoardDeleteButton.value?.$el.focus();
}

async function cancelBoardDelete() {
    isConfirmingBoardDelete.value = false;
    await nextTick();
    deleteBoardButton.value?.$el.focus();
}

function deleteBoard() {
    if (!props.canDelete || !isReadOnly.value || isDeletingBoard.value) {
        return;
    }

    isDeletingBoard.value = true;
    const deletedName = boardName.value;

    deleteRequest
        .delete(boardRoutes.destroy(props.board.id).url, {
            onSuccess: () =>
                router.visit(workspaceHomeUrl.value, {
                    replace: true,
                    onSuccess: () => toast.success(`Deleted “${deletedName}”`),
                }),
            onError: (errors) => failArchivedBoardAction('delete', 422, errors.board),
            onHttpException: (response) => failArchivedBoardAction('delete', response.status),
            onNetworkError: () => failArchivedBoardAction('delete'),
        })
        .catch(() => {});
}

/*
|--------------------------------------------------------------------------
| Members
|--------------------------------------------------------------------------
*/

const showMembersDialog = ref(false);
const showLeaveDialog = ref(false);

/** A member change came back 404: the person was already gone, or the user lost the board itself. */
function onMemberNotFound(failureMessage: string) {
    confirmBoardAccess(() => {
        toast.error(failureMessage, { description: 'The member list changed. It has been refreshed.' });
        reloadQuietly(['members', 'addableMembers']);
    });
}

/*
|--------------------------------------------------------------------------
| Canvas: scrolling, mobile list navigation
|--------------------------------------------------------------------------
*/

const canvas = useTemplateRef<HTMLElement>('canvas');
const listNav = useTemplateRef<HTMLElement>('list-nav');
const {
    activeListIndex,
    columnElements,
    onCanvasScroll,
    onCanvasWheel,
    stopWheelScroll,
    scrollBehavior,
    jumpToList,
    scrollListIntoView,
    scrollToEnd,
} = useCanvasScroll(canvas, listNav, prefersReducedMotion);

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
    disabled: isReadOnly.value,
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

/*
|--------------------------------------------------------------------------
| Lists
|--------------------------------------------------------------------------
*/

function persistListOrder() {
    if (isReadOnly.value) {
        return;
    }

    send(
        'patch',
        boardListRoutes.reorder(props.board.id).url,
        { boardLists: lists.value.map((list, index) => ({ id: list.id, order: index })) },
        'Could not save the list order.',
    );
}

function onListsChange(event: SortableChangeEvent<BoardListType>) {
    if (isReadOnly.value) {
        return;
    }

    if (!event.moved) {
        return;
    }

    persistListOrder();
    announce(
        `Moved list ${event.moved.element.name} to position ${event.moved.newIndex + 1} of ${lists.value.length}.`,
    );
}

async function moveList(list: BoardListType, direction: -1 | 1) {
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

    list.name = name;
    send('patch', updateListUrl(list), { name }, 'Could not rename the list.');
}

function recolorList(list: BoardListType, color: string | null) {
    if (isReadOnly.value) {
        return;
    }

    list.color = color;
    send('patch', updateListUrl(list), { color }, 'Could not change the list colour.');
}

function archiveList(list: BoardListType) {
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

    send(
        'patch',
        cardRoutes.restore({ board_list: card.board_list_id, card: card.id }).url,
        {},
        `Could not restore “${card.name}”.`,
        undefined,
        reloadArchivedItems,
    );
}

const listComposer = useTemplateRef<InstanceType<typeof ListComposer>>('list-composer');

function openListComposer() {
    if (isReadOnly.value) {
        return;
    }

    listComposer.value?.open();
}

async function onListAdded() {
    announce('List added.');
    await nextTick();
    scrollToEnd();
}

function onRequestFailed(message: string, status?: number) {
    if (status === 404) {
        confirmBoardAccess(() => toast.error(message));
    } else {
        toast.error(message);
    }

    // The board was archived meanwhile: show it read-only.
    if (status === 403) {
        reloadQuietly(['board']);
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
            closeCard(() => onRequestFailed('Could not open the card.', response.status));

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
    if (isDraggingOrJustDropped()) {
        return;
    }

    descriptionRequest?.cancel();
    showCard(card, cardUrl(card), () => {
        if (activeCardDescription.value === undefined) {
            loadActiveCardDescription();
        }
    });
}

function followOpenCard(card: Card | null) {
    if (card) {
        showCard(card, cardUrl(card), () => {
            if (activeCardDescription.value === undefined) {
                loadActiveCardDescription();
            }
        });

        return;
    }

    closeCard(() => toast('This card is gone', { description: 'Someone else deleted it or archived its list.' }));
    announce('The open card was deleted or its list was archived by someone else.');
}

function closeCard(onFinish?: () => void) {
    // A description still loading would reopen the card when it arrives.
    descriptionRequest?.cancel();
    showCard(null, boardRoutes.show(props.board.id).url, onFinish);
}

function persistCardOrder(list: BoardListType) {
    if (isReadOnly.value) {
        return;
    }

    list.cards.forEach((card, index) => (card.order = index));

    send(
        'patch',
        cardRoutes.reorder(list.id).url,
        { cards: list.cards.map(({ id, order }) => ({ id, order })) },
        'Could not save the card order.',
    );
}

function onCardsChange(list: BoardListType, event: SortableChangeEvent<Card>) {
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

    card.name = name;
    send(
        'patch',
        cardRoutes.update({ board_list: card.board_list_id, card: card.id }).url,
        { name },
        'Could not rename the card.',
    );
}

function describeCard(card: Card, description: string) {
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

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
    if (isReadOnly.value) {
        return;
    }

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
            <BoardHeader
                ref="board-header"
                :board-name="boardName"
                :can-archive="canArchive"
                :can-leave="canLeave"
                :is-archiving="isArchiving"
                :is-read-only="isReadOnly"
                :is-starred="isStarred"
                :members="members"
                :owner="owner"
                :workspace="workspace"
                :workspace-home-url="workspaceHomeUrl"
                :workspace-name="workspaceName"
                @add-list="openListComposer"
                @archive-board="archiveBoard"
                @leave-board="showLeaveDialog = true"
                @rename="renameBoard"
                @show-archived-items="showArchivedItems = true"
                @show-members="showMembersDialog = true"
                @toggle-star="toggleStar"
            />

            <section
                v-if="isReadOnly"
                aria-labelledby="archived-board-title"
                class="border-b bg-blush/50 px-4 py-3 sm:px-6"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <h2 id="archived-board-title" class="min-w-0 flex-1 text-sm font-semibold">
                        This board is archived
                    </h2>
                    <div
                        v-if="(canUnarchive || canDelete || canLeave) && !isConfirmingBoardDelete"
                        class="flex shrink-0 items-center gap-2"
                    >
                        <Button
                            v-if="canUnarchive"
                            :disabled="isRestoring"
                            class="cursor-pointer max-sm:flex-1"
                            size="sm"
                            @click="restoreBoard"
                        >
                            <ArchiveRestore aria-hidden="true" />
                            {{ isRestoring ? 'Restoring…' : 'Restore board' }}
                        </Button>
                        <Button
                            v-if="canDelete"
                            ref="delete-board-button"
                            :disabled="isRestoring"
                            class="cursor-pointer text-destructive hover:bg-destructive/10 hover:text-destructive max-sm:flex-1"
                            size="sm"
                            variant="ghost"
                            @click="askToDeleteBoard"
                        >
                            <Trash2 aria-hidden="true" />
                            Delete board
                        </Button>
                        <Button
                            v-if="canLeave"
                            class="cursor-pointer hover:border-destructive/40 hover:bg-destructive/10 hover:text-destructive max-sm:flex-1"
                            size="sm"
                            variant="outline"
                            @click="showLeaveDialog = true"
                        >
                            <LogOut aria-hidden="true" />
                            Leave board
                        </Button>
                    </div>
                </div>
                <div
                    v-if="canDelete && isConfirmingBoardDelete"
                    aria-label="Confirm deleting the board"
                    class="mt-3 flex flex-col gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-3 sm:flex-row sm:items-center sm:justify-between"
                    role="group"
                    @keydown.esc.stop.prevent="cancelBoardDelete"
                >
                    <p class="text-xs text-foreground sm:text-sm">
                        <span class="font-semibold">Delete permanently?</span>
                        {{ boardContents }}
                    </p>
                    <div class="flex shrink-0 justify-end gap-2">
                        <Button
                            ref="cancel-board-delete-button"
                            class="cursor-pointer"
                            size="sm"
                            variant="ghost"
                            @click="cancelBoardDelete"
                        >
                            Cancel
                        </Button>
                        <Button
                            :disabled="isDeletingBoard"
                            class="cursor-pointer"
                            size="sm"
                            variant="destructive"
                            @click="deleteBoard"
                        >
                            <Trash2 aria-hidden="true" />
                            {{ isDeletingBoard ? 'Deleting…' : 'Delete board' }}
                        </Button>
                    </div>
                </div>
            </section>

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
                                :is-read-only="isReadOnly"
                                @archive="archiveList(element)"
                                @cards-change="onCardsChange"
                                @drag-end="onDragEnd"
                                @drag-start="onDragStart('card')"
                                @move="moveList(element, $event)"
                                @open-card="openCard"
                                @recolor="recolorList(element, $event)"
                                @rename="renameList(element, $event)"
                                @request-failed="onRequestFailed"
                            />
                        </li>
                    </template>

                    <template #footer>
                        <ListComposer
                            v-if="!isReadOnly"
                            ref="list-composer"
                            :board-id="board.id"
                            :has-lists="lists.length > 0"
                            @added="onListAdded"
                            @opened="scrollToEnd"
                            @request-failed="onRequestFailed"
                        />
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
            :is-read-only="isReadOnly"
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
            :can-manage-members="canManageMembers && !isReadOnly"
            :members="members"
            :owner="owner"
            :workspace-name="workspaceName"
            @not-found="onMemberNotFound"
        />

        <LeaveBoardDialog
            v-if="canLeave"
            v-model:open="showLeaveDialog"
            :board-id="board.id"
            :board-name="boardName"
            :workspace-name="workspaceName"
            @not-found="(message) => confirmBoardAccess(() => toast.error(message))"
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
