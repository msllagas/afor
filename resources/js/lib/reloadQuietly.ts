import { router } from '@inertiajs/vue3';

/**
 * Refresh some props in the background without interrupting other visits.
 * If it fails the page keeps what it has and catches up on the next visit, so no error is shown.
 */
export function reloadQuietly(only: string[]) {
    router.reload({
        only,
        async: true,
        onHttpException: () => false,
        onNetworkError: () => false,
    });
}
