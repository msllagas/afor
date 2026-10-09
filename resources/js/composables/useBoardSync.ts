import { reloadQuietly } from '@/lib/reloadQuietly';
import { dashboard } from '@/routes';
import boardRoutes from '@/routes/boards';
import type { Board, BoardList } from '@/types';
import type { RequestPayload } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

/** A drop can fire a click on the card that was just dragged. */
const CLICK_AFTER_DROP_MS = 250;

function cloneLists(boardLists: BoardList[]): BoardList[] {
    return boardLists.map((list) => ({ ...list, cards: list.cards.map((card) => ({ ...card })) }));
}

/**
 * The board page's local copy of the board, and everything that keeps it in step with the server.
 *
 * @param options.board The board prop as the server last sent it.
 * @param options.isEditingName Whether the board name is being edited, so a snapshot doesn't overwrite it.
 * @param options.cancelDescriptionRequest Stops the open card's description from loading.
 */
export function useBoardSync(options: {
    board: () => Board;
    isEditingName: () => boolean;
    cancelDescriptionRequest: () => void;
}) {
    /*
    |--------------------------------------------------------------------------
    | Local board state
    |--------------------------------------------------------------------------
    | Lists and cards are edited optimistically on a local copy. Server
    | snapshots replace it only when nothing is being dragged or saved,
    | so a slow response can't snap a card back mid-move.
    */

    const lists = ref<BoardList[]>(cloneLists(options.board().board_lists));
    const boardName = ref(options.board().name);
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
        lists.value = cloneLists(options.board().board_lists);

        if (!options.isEditingName()) {
            boardName.value = options.board().name;
        }
    }

    watch(options.board, applyServerBoard);

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

    function isDraggingOrJustDropped() {
        return isDragging.value || Date.now() - lastDragEndedAt < CLICK_AFTER_DROP_MS;
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
        options.cancelDescriptionRequest();
        router.visit(boardRoutes.show(options.board().id).url, {
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
        lists.value = cloneLists(options.board().board_lists);
        boardName.value = options.board().name;

        if (status === 404) {
            confirmBoardAccess(() => toast.error(message));

            return;
        }

        // A 403 means the board was archived meanwhile; the reload below shows it read-only.
        toast.error(
            message,
            status === 403 ? { description: 'This board is archived. Restore it to make changes.' } : undefined,
        );
        // The open card's description is reloaded too, in case it was the change that failed.
        reloadQuietly(['board', 'selectedCard']);
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

    return {
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
    };
}
