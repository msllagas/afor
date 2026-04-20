export function useTextAreaAutoResize() {
    function autoResize(eventOrEl?: Event | HTMLTextAreaElement | null) {
        const el = eventOrEl instanceof Event ? (eventOrEl.target as HTMLTextAreaElement) : eventOrEl;

        if (!el) return;
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    }

    return { autoResize };
}
