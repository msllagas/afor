const TIME_UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31_536_000],
    ['month', 2_592_000],
    ['week', 604_800],
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

const relativeTime = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

/** How long ago a timestamp was, such as "2 hours ago" or "yesterday"; anything under a minute is "just now". */
export function formatTimeAgo(timestamp: string): string {
    const seconds = (Date.parse(timestamp) - Date.now()) / 1000;
    const [unit, size] = TIME_UNITS.find(([, unitSize]) => Math.abs(seconds) >= unitSize) ?? [];

    return unit && size ? relativeTime.format(Math.round(seconds / size), unit) : 'just now';
}

const DAY_SECONDS = 86_400;

/**
 * How long an archived item has before the daily purge removes it, such as "27 days left". An item in its last
 * day reads "removed within a day".
 */
export function formatTimeLeft(archivedAt: string, retentionDays: number): string {
    const daysLeft = Math.ceil(retentionDays - (Date.now() - Date.parse(archivedAt)) / 1000 / DAY_SECONDS);

    return daysLeft <= 1 ? 'removed within a day' : `${daysLeft} days left`;
}
