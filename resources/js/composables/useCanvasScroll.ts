import type { Ref, ShallowRef } from 'vue';
import { onBeforeUnmount, ref, watch } from 'vue';

const WHEEL_LINE_HEIGHT = 40;
const WHEEL_EASING = 0.18;

/**
 * Scrolling for the board canvas: which list is in view, wheel scrolling sideways,
 * and jumping to a list from the mobile list navigation.
 */
export function useCanvasScroll(
    canvas: Readonly<ShallowRef<HTMLElement | null>>,
    listNav: Readonly<ShallowRef<HTMLElement | null>>,
    prefersReducedMotion: Readonly<Ref<boolean>>,
) {
    const activeListIndex = ref(0);
    let scrollFrame = 0;
    let wheelTarget: number | null = null;
    let wheelFrame = 0;

    function columnElements() {
        return Array.from(canvas.value?.querySelectorAll<HTMLElement>('[data-list-id]') ?? []);
    }

    function updateActiveList() {
        scrollFrame = 0;
        const scroller = canvas.value;

        if (!scroller) {
            return;
        }

        const center = scroller.scrollLeft + scroller.clientWidth / 2;
        let closestIndex = 0;
        let closestDistance = Infinity;

        columnElements().forEach((column, index) => {
            const distance = Math.abs(column.offsetLeft + column.offsetWidth / 2 - center);

            if (distance < closestDistance) {
                closestDistance = distance;
                closestIndex = index;
            }
        });

        activeListIndex.value = closestIndex;
    }

    function onCanvasScroll() {
        if (!scrollFrame) {
            scrollFrame = requestAnimationFrame(updateActiveList);
        }
    }

    // Turn vertical wheel movement into horizontal scrolling, except over a list that scrolls itself.
    function onCanvasWheel(event: WheelEvent) {
        const scroller = canvas.value;

        if (!scroller || event.ctrlKey || Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
            return;
        }

        const cardScroller = (event.target as HTMLElement).closest<HTMLElement>('[data-card-scroller]');

        if (cardScroller && cardScroller.scrollHeight > cardScroller.clientHeight) {
            return;
        }

        // Line-based wheels (mostly Firefox) report rows rather than pixels.
        const distance =
            event.deltaMode === WheelEvent.DOM_DELTA_LINE ? event.deltaY * WHEEL_LINE_HEIGHT : event.deltaY;

        // Trackpads already send a smooth stream of small steps, and phones snap list by list.
        const isTrackpad = event.deltaMode === WheelEvent.DOM_DELTA_PIXEL && Math.abs(distance) < 50;

        if (prefersReducedMotion.value || isTrackpad) {
            stopWheelScroll();
            scroller.scrollLeft += distance;

            return;
        }

        if (getComputedStyle(scroller).scrollSnapType !== 'none') {
            scroller.scrollBy({ left: distance, behavior: 'smooth' });

            return;
        }

        // Glide towards a target that each notch pushes further, so fast scrolling stays fluid.
        const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;
        wheelTarget = Math.min(Math.max((wheelTarget ?? scroller.scrollLeft) + distance, 0), maxScrollLeft);

        if (!wheelFrame) {
            wheelFrame = requestAnimationFrame(stepWheelScroll);
        }
    }

    function stepWheelScroll() {
        const scroller = canvas.value;

        if (!scroller || wheelTarget === null) {
            stopWheelScroll();

            return;
        }

        const remaining = wheelTarget - scroller.scrollLeft;

        if (Math.abs(remaining) < 1) {
            scroller.scrollLeft = wheelTarget;
            stopWheelScroll();

            return;
        }

        // Move at least a pixel per frame so rounding can't stall the glide.
        scroller.scrollLeft += Math.sign(remaining) * Math.max(Math.abs(remaining) * WHEEL_EASING, 1);
        wheelFrame = requestAnimationFrame(stepWheelScroll);
    }

    /** Hand control back to the user or to another scroll, such as jumping to a list. */
    function stopWheelScroll() {
        cancelAnimationFrame(wheelFrame);
        wheelFrame = 0;
        wheelTarget = null;
    }

    function scrollBehavior(): ScrollBehavior {
        return prefersReducedMotion.value ? 'auto' : 'smooth';
    }

    function jumpToList(index: number) {
        stopWheelScroll();
        columnElements()[index]?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'center' });
    }

    function scrollListIntoView(listId: string) {
        stopWheelScroll();
        columnElements()
            .find((column) => column.dataset.listId === listId)
            ?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'nearest' });
    }

    /** Bring the end of the board into view, where a new list goes. */
    function scrollToEnd() {
        stopWheelScroll();
        canvas.value?.scrollTo({ left: canvas.value.scrollWidth, behavior: scrollBehavior() });
    }

    watch(activeListIndex, (index) => {
        listNav.value
            ?.querySelectorAll('button')
            [index]?.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest', inline: 'nearest' });
    });

    onBeforeUnmount(() => {
        cancelAnimationFrame(scrollFrame);
        stopWheelScroll();
    });

    return {
        activeListIndex,
        columnElements,
        onCanvasScroll,
        onCanvasWheel,
        stopWheelScroll,
        scrollBehavior,
        jumpToList,
        scrollListIntoView,
        scrollToEnd,
    };
}
