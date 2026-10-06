import { reloadQuietly } from '@/lib/reloadQuietly';
import { favorite } from '@/routes/workspaces/boards';
import type { Board } from '@/types';
import { useHttp } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

/**
 * Star and unstar boards. The caller shows the new star straight away;
 * `undo` puts it back if the server doesn't take the change.
 */
export function useBoardStar() {
    const http = useHttp();

    /**
     * @param onHttpFailure Lets the board page check a 404 wasn't the board itself going away before showing the failure.
     */
    function toggleStar(
        board: Pick<Board, 'id' | 'workspace_id'>,
        undo: () => void,
        onHttpFailure: (response: { status: number }, showFailure: () => void) => void = (_, showFailure) =>
            showFailure(),
    ) {
        const fail = () => {
            undo();
            toast.error('Could not update the star. Try again.');
        };

        http.post(favorite({ workspace: board.workspace_id, board: board.id }).url, {
            // Refresh the sidebar's Starred section; if that fails it catches up on the next visit.
            onSuccess: () => reloadQuietly(['starredBoards']),
            onError: fail,
            onHttpException: (response) => onHttpFailure(response, fail),
            onNetworkError: fail,
        }).catch(() => {});
    }

    return { isStarring: computed(() => http.processing), toggleStar };
}
