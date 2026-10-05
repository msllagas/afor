<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import boardRoutes from '@/routes/boards';
import workspaceRoutes from '@/routes/workspaces';
import type { Board, Workspace } from '@/types';
import { useHttp, usePage } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, CircleAlert, Search, Trash2 } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

type BoardAction = 'restore' | 'delete';

const SEARCH_THRESHOLD = 5;
const PREVIEW_LIST_CAP = 4;
const PREVIEW_CARD_CAP = 3;
const TIME_UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31_536_000],
    ['month', 2_592_000],
    ['week', 604_800],
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

const props = defineProps<{
    workspace: Workspace;
}>();

const emit = defineEmits<{
    unarchiveBoard: [board: Board];
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const archivedBoardsRequest = useHttp();
const boardRequest = useHttp();
const relativeTime = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

const archivedBoards = ref<Board[] | null>(null);
const hasLoadFailed = ref(false);
const search = ref('');
const confirmingDeleteId = ref<string | null>(null);
const exitActions = ref<Record<string, BoardAction>>({});
const announcement = ref('');
const listRef = ref<HTMLElement | null>(null);

const filteredBoards = computed(() => {
    const query = search.value.trim().toLowerCase();
    const boards = archivedBoards.value ?? [];

    return query ? boards.filter((board) => board.name.toLowerCase().includes(query)) : boards;
});

const showSearch = computed(() => (archivedBoards.value?.length ?? 0) > SEARCH_THRESHOLD);

function loadArchivedBoards() {
    hasLoadFailed.value = false;

    archivedBoardsRequest
        .get(workspaceRoutes.boards.archived(props.workspace.id).url, {
            onSuccess: (data) => {
                archivedBoards.value = data as Board[];
            },
            onHttpException: () => (hasLoadFailed.value = archivedBoards.value === null),
            onNetworkError: () => (hasLoadFailed.value = archivedBoards.value === null),
        })
        .catch(() => {});
}

function formatArchivedAt(board: Board) {
    const archiver = board.archiver?.id === page.props.auth.user.id ? 'you' : board.archiver?.name;
    const byline = archiver ? ` by ${archiver}` : '';

    if (!board.archived_at) {
        return `Archived${byline}`;
    }

    const seconds = board.archived_at - Date.now() / 1000;
    const [unit, size] = TIME_UNITS.find(([, unitSize]) => Math.abs(seconds) >= unitSize) ?? [];
    const when = unit && size ? relativeTime.format(Math.round(seconds / size), unit) : 'just now';

    return `Archived ${when}${byline}`;
}

function describeContents(board: Board) {
    const lists = board.board_lists ?? [];
    const cardCount = lists.reduce((total, list) => total + (list.cards_count ?? 0), 0);

    if (!lists.length) {
        return 'It has no lists.';
    }

    return `Its ${lists.length} ${lists.length === 1 ? 'list' : 'lists'} and ${cardCount} ${cardCount === 1 ? 'card' : 'cards'} go with it.`;
}

function announce(message: string) {
    announcement.value = '';
    nextTick(() => (announcement.value = message));
}

function focusById(id: string) {
    nextTick(() => document.getElementById(id)?.focus());
}

/**
 * Removes the row with an exit animation that matches the action, then keeps
 * keyboard focus inside the list by moving it to the neighbouring row.
 */
async function removeBoard(board: Board, action: BoardAction) {
    const visibleIndex = filteredBoards.value.findIndex((existing) => existing.id === board.id);
    const index = archivedBoards.value?.findIndex((existing) => existing.id === board.id) ?? -1;

    if (index === -1) {
        return -1;
    }

    exitActions.value[board.id] = action;
    confirmingDeleteId.value = null;
    await nextTick();

    archivedBoards.value?.splice(index, 1);
    await nextTick();

    const restoreButtons = listRef.value?.querySelectorAll<HTMLElement>(
        '.archived-row:not(.archived-row-leave-active) [data-restore-button]',
    );
    const nextFocus = restoreButtons?.[Math.min(visibleIndex, restoreButtons.length - 1)];
    (nextFocus ?? listRef.value)?.focus();

    return index;
}

function restoreRemovedBoard(board: Board, index: number) {
    delete exitActions.value[board.id];

    if (archivedBoards.value && !archivedBoards.value.some((existing) => existing.id === board.id)) {
        archivedBoards.value.splice(Math.min(index, archivedBoards.value.length), 0, board);
    }
}

function handleBoardFailure(board: Board, index: number, action: BoardAction, status?: number, message?: string) {
    if (status === 404) {
        if (action === 'restore') {
            toast.error(`"${board.name}" no longer exists`, {
                description: 'Someone else deleted it, so it can’t be restored.',
            });
        }

        return;
    }

    restoreRemovedBoard(board, index);

    const fallbackMessages: Record<number, string> = {
        403: 'You don’t have permission to change this board.',
        419: 'Your session expired. Refresh the page, then try again.',
    };

    toast.error(action === 'restore' ? `Couldn’t restore "${board.name}"` : `Couldn’t delete "${board.name}"`, {
        description:
            message ??
            (status === undefined
                ? 'Check your connection, then try again.'
                : (fallbackMessages[status] ?? 'Something went wrong on our end. Try again in a moment.')),
        action: {
            label: 'Try again',
            onClick: () => (action === 'restore' ? restoreBoard(board) : deleteBoard(board)),
        },
    });
}

async function restoreBoard(board: Board) {
    const index = await removeBoard(board, 'restore');

    boardRequest
        .patch(boardRoutes.unarchive(board.id).url, {
            onSuccess: (data) => {
                emit('unarchiveBoard', { ...board, ...(data as Board) });
                announce(`Restored ${board.name}.`);
            },
            onError: (errors) => handleBoardFailure(board, index, 'restore', 422, errors.board),
            onHttpException: (response) => handleBoardFailure(board, index, 'restore', response.status),
            onNetworkError: () => handleBoardFailure(board, index, 'restore'),
        })
        .catch(() => {});
}

async function deleteBoard(board: Board) {
    const index = await removeBoard(board, 'delete');

    boardRequest
        .delete(boardRoutes.destroy(board.id).url, {
            onSuccess: () => announce(`Deleted ${board.name}.`),
            onError: (errors) => handleBoardFailure(board, index, 'delete', 422, errors.board),
            onHttpException: (response) => handleBoardFailure(board, index, 'delete', response.status),
            onNetworkError: () => handleBoardFailure(board, index, 'delete'),
        })
        .catch(() => {});
}

function askToDelete(board: Board) {
    confirmingDeleteId.value = board.id;
    focusById(`cancel-delete-${board.id}`);
}

function cancelDelete() {
    const boardId = confirmingDeleteId.value;
    confirmingDeleteId.value = null;

    if (boardId) {
        focusById(`delete-board-${boardId}`);
    }
}

function handleEscape(event: KeyboardEvent) {
    if (confirmingDeleteId.value) {
        event.preventDefault();
        cancelDelete();
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        search.value = '';
        confirmingDeleteId.value = null;
        loadArchivedBoards();
    } else {
        archivedBoardsRequest.cancel();
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="flex max-h-[calc(100dvh-5rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-xl"
            @escape-key-down="handleEscape"
        >
            <DialogHeader class="gap-1.5 border-b px-5 pt-5 pr-12 pb-4 text-left sm:px-6">
                <DialogTitle class="flex items-center gap-2 text-xl font-semibold tracking-tight">
                    Archived boards
                    <span
                        v-if="archivedBoards?.length"
                        class="rounded-full bg-muted px-2 py-0.5 font-sans text-xs font-medium text-muted-foreground tabular-nums"
                    >
                        {{ archivedBoards.length }}
                    </span>
                </DialogTitle>
                <DialogDescription>
                    Restore a board to put it back in {{ workspace.name }}, or delete it for good.
                </DialogDescription>
                <div v-if="showSearch" class="relative mt-2">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="search"
                        aria-label="Search archived boards"
                        class="pl-9"
                        placeholder="Search by name"
                        type="search"
                    />
                </div>
            </DialogHeader>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4 sm:px-6">
                <div v-if="hasLoadFailed" class="py-4">
                    <Empty class="p-0">
                        <EmptyHeader>
                            <EmptyMedia class="bg-destructive/10 text-destructive" variant="icon">
                                <CircleAlert />
                            </EmptyMedia>
                            <EmptyTitle>Couldn’t load archived boards</EmptyTitle>
                            <EmptyDescription>Check your connection, then try again.</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button
                                :disabled="archivedBoardsRequest.processing"
                                class="cursor-pointer"
                                size="sm"
                                variant="outline"
                                @click="loadArchivedBoards"
                            >
                                {{ archivedBoardsRequest.processing ? 'Trying again…' : 'Try again' }}
                            </Button>
                        </EmptyContent>
                    </Empty>
                </div>

                <div v-else-if="archivedBoards === null" aria-busy="true" class="flex flex-col gap-2">
                    <span class="sr-only">Loading archived boards</span>
                    <div v-for="i in 3" :key="i" class="flex items-center gap-3 rounded-xl border p-3">
                        <Skeleton class="h-10 w-14 rounded-md bg-blush/60" />
                        <div class="flex flex-1 flex-col gap-2">
                            <Skeleton :class="i % 2 ? 'w-2/3' : 'w-1/2'" class="h-3.5" />
                            <Skeleton class="h-3 w-1/3" />
                        </div>
                    </div>
                </div>

                <div v-else ref="listRef" class="outline-none" tabindex="-1">
                    <TransitionGroup
                        aria-label="Archived boards"
                        class="relative space-y-2"
                        name="archived-row"
                        tag="ul"
                    >
                        <li
                            v-for="board in filteredBoards"
                            :key="board.id"
                            :class="
                                confirmingDeleteId === board.id
                                    ? 'border-destructive/40 bg-destructive/5'
                                    : 'bg-card hover:border-primary/30'
                            "
                            :data-exit="exitActions[board.id]"
                            class="archived-row grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-3 rounded-xl border p-3 sm:grid-cols-[auto_minmax(0,1fr)_auto]"
                        >
                            <div
                                aria-hidden="true"
                                class="flex h-10 w-14 items-start gap-0.5 overflow-hidden rounded-md bg-blush/60 p-1"
                            >
                                <div
                                    v-for="list in (board.board_lists ?? []).slice(0, PREVIEW_LIST_CAP)"
                                    :key="list.id"
                                    :class="`list-${list.color ?? 'neutral'}`"
                                    class="flex w-2.5 shrink-0 flex-col gap-px rounded-xs bg-(--list-bg) p-px pt-1"
                                >
                                    <span
                                        v-for="card in Math.min(list.cards_count ?? 0, PREVIEW_CARD_CAP)"
                                        :key="card"
                                        class="h-1 rounded-[1px] bg-card/90"
                                    />
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ board.name }}</p>
                                <p class="text-xs text-muted-foreground sm:truncate">{{ formatArchivedAt(board) }}</p>
                            </div>

                            <div
                                v-if="confirmingDeleteId === board.id"
                                class="col-span-full flex flex-col gap-3 border-t border-destructive/20 pt-3 sm:flex-row sm:items-center sm:justify-between"
                                role="group"
                                :aria-label="`Confirm deleting ${board.name}`"
                            >
                                <p class="text-xs text-foreground">
                                    <span class="font-semibold">Delete permanently?</span>
                                    {{ describeContents(board) }}
                                </p>
                                <div class="flex shrink-0 justify-end gap-2">
                                    <Button
                                        :id="`cancel-delete-${board.id}`"
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="ghost"
                                        @click="cancelDelete"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="destructive"
                                        @click="deleteBoard(board)"
                                    >
                                        <Trash2 aria-hidden="true" />
                                        Delete board
                                    </Button>
                                </div>
                            </div>

                            <div v-else class="col-span-full flex items-center justify-end gap-1.5 sm:col-span-1">
                                <Button
                                    class="cursor-pointer max-sm:flex-1"
                                    data-restore-button
                                    size="sm"
                                    variant="outline"
                                    @click="restoreBoard(board)"
                                >
                                    <ArchiveRestore aria-hidden="true" />
                                    Restore<span class="sr-only"> {{ board.name }}</span>
                                </Button>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                :id="`delete-board-${board.id}`"
                                                :aria-label="`Delete ${board.name} permanently`"
                                                class="size-8 cursor-pointer text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                                size="icon"
                                                variant="ghost"
                                                @click="askToDelete(board)"
                                            >
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Delete permanently</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </li>
                    </TransitionGroup>

                    <Transition name="archived-empty">
                        <p
                            v-if="search.trim() && !filteredBoards.length && archivedBoards.length"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            No archived boards match “{{ search.trim() }}”.
                        </p>
                        <Empty v-else-if="!archivedBoards.length" class="p-0 py-4">
                            <EmptyHeader>
                                <EmptyMedia class="bg-blush text-blush-foreground" variant="icon">
                                    <Archive />
                                </EmptyMedia>
                                <EmptyTitle>Nothing archived</EmptyTitle>
                                <EmptyDescription>
                                    Boards you archive in {{ workspace.name }} wait here until you restore or delete
                                    them.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    </Transition>
                </div>
            </div>

            <p aria-live="polite" class="sr-only">{{ announcement }}</p>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.archived-row {
    transition:
        background-color 200ms ease,
        border-color 200ms ease;
}

.archived-row-move,
.archived-row-enter-active,
.archived-row-leave-active {
    transition:
        transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
        opacity 240ms ease,
        background-color 200ms ease,
        border-color 200ms ease;
}

.archived-row-leave-active {
    position: absolute;
    inset-inline: 0;
    pointer-events: none;
}

.archived-row-enter-from {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

.archived-row-leave-to {
    opacity: 0;
    transform: scale(0.97);
}

.archived-row-leave-active[data-exit='restore'] {
    border-color: color-mix(in oklab, var(--primary) 45%, transparent);
    background-color: color-mix(in oklab, var(--primary) 8%, var(--card));
}

.archived-row-leave-to[data-exit='restore'] {
    transform: translateX(2rem);
}

.archived-row-leave-active[data-exit='delete'] {
    border-color: color-mix(in oklab, var(--destructive) 45%, transparent);
    background-color: color-mix(in oklab, var(--destructive) 8%, var(--card));
}

.archived-row-leave-to[data-exit='delete'] {
    transform: scale(0.92);
    filter: blur(2px);
}

.archived-empty-enter-active {
    transition: opacity 200ms ease 160ms;
}

.archived-empty-enter-from {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .archived-row-move,
    .archived-row-enter-active,
    .archived-row-leave-active,
    .archived-empty-enter-active {
        transition: opacity 150ms ease;
    }

    .archived-row-enter-from,
    .archived-row-leave-to,
    .archived-row-leave-to[data-exit] {
        transform: none;
        filter: none;
    }
}
</style>
