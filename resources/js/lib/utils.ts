import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function reorderDraggable(lists: any[], oldIndex: number, newIndex: number) {
    const start = Math.min(oldIndex, newIndex);
    const end = Math.max(oldIndex, newIndex);

    // Items inside the list that are within the movements of indexes
    const affectedItems = lists.slice(start, end + 1);
    const changed = []

    for (let i = start; i <= end; i++) {
        const item = affectedItems[i - start];
        if (item.order !== i) {
            changed.push({ ...item, order: i });
        }
    }
    return changed;
}
