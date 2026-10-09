import { onReconnect, onWorkspaceEvent, startRealtime } from '@/lib/realtime';
import { dashboard } from '@/routes';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { onScopeDispose } from 'vue';
import { toast } from 'vue-sonner';

const REFRESH_DELAY_MS = 500;

/**
 * Keep a workspace page in step with what other people change. The server only says that a board in the workspace
 * changed, or the workspace or its people did. The page fetches the matching props again, and the fetch only returns
 * what the user may still see: a board they were added to appears, and a workspace they lost sends them away.
 *
 * @param options.workspaceId The workspace the page shows, or null for a page that lists all of them.
 * @param options.boardProps Props to fetch again when a board changes, its lists and cards included. The sidebar's
 * starred boards come with them.
 * @param options.workspaceProps Props to fetch again when the workspace, its members, or how they appear change.
 */
export function useWorkspaceSync(options: {
    workspaceId: string | null;
    boardProps?: string[];
    workspaceProps?: string[];
}) {
    if (!startRealtime()) {
        return;
    }

    const boardProps = options.boardProps?.length ? [...options.boardProps, 'starredBoards'] : [];
    const workspaceProps = options.workspaceProps ?? [];
    const propsToFetch = new Set<string>();
    let isDisposed = false;
    let hasLostAccess = false;

    onScopeDispose(() => (isDisposed = true));

    function leaveInaccessibleWorkspace() {
        if (hasLostAccess) {
            return;
        }

        hasLostAccess = true;
        router.visit(dashboard().url, {
            replace: true,
            onFinish: () =>
                toast.error('You no longer have access to this workspace', {
                    description: 'It was deleted, or you were removed from it.',
                }),
        });
    }

    const refresh = useDebounceFn(() => {
        if (isDisposed || hasLostAccess || propsToFetch.size === 0) {
            return;
        }

        const only = [...propsToFetch];
        propsToFetch.clear();

        router.reload({
            only,
            async: true,
            onHttpException: (response) => {
                if (response.status === 403 || response.status === 404) {
                    leaveInaccessibleWorkspace();
                }

                return false;
            },
            onNetworkError: () => false,
        });
    }, REFRESH_DELAY_MS);

    function fetchSoon(props: string[]) {
        if (props.length === 0) {
            return;
        }

        props.forEach((prop) => propsToFetch.add(prop));
        refresh();
    }

    onWorkspaceEvent((workspaceId, event) => {
        if (options.workspaceId === null || workspaceId === options.workspaceId) {
            fetchSoon(event === 'board' ? boardProps : workspaceProps);
        }
    });

    onReconnect(() => fetchSoon([...boardProps, ...workspaceProps]));
}
