export function useTextAreaAutoResize() {
    const autoResize = (event: Event) => {
        const target = event.target as HTMLTextAreaElement | null;
        if (!target) return;

        target.style.height = 'auto';
        target.style.height = `${target.scrollHeight}px`;
    };

    return { autoResize };
}
