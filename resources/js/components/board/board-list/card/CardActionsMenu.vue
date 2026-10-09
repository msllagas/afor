<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import type { BoardList } from '@/types';
import {
    ArrowDown,
    ArrowDownToLine,
    ArrowLeft,
    ArrowRightLeft,
    ArrowUp,
    ArrowUpToLine,
    Check,
    ChevronRight,
    Ellipsis,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { ListboxContent, ListboxFilter, ListboxItem, ListboxRoot } from 'reka-ui';
import { computed, nextTick, ref, useId, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    boardLists: BoardList[];
    currentListId: string;
    cardId: string;
    /** The board is archived, so the card can't be moved or deleted. */
    isReadOnly?: boolean;
}>();

const emit = defineEmits<{
    move: [boardListId: string];
    reorder: [position: number];
    delete: [];
}>();

const isOpen = ref(false);
const view = ref<'actions' | 'move'>('actions');
const query = ref('');
const listbox = useTemplateRef<{ highlightFirstItem: () => void }>('listbox');
const moveButton = useTemplateRef<HTMLButtonElement>('move-button');
const positionGroup = useTemplateRef<HTMLElement>('position-group');

// "Delete card" hands focus to the dialog's delete prompt, so the popover must not pull it back.
let isMovingFocus = false;

const canMove = computed(() => !props.isReadOnly && props.boardLists.length > 1);

const cardsInList = computed(() => props.boardLists.find(({ id }) => id === props.currentListId)?.cards ?? []);
const position = computed(() => cardsInList.value.findIndex(({ id }) => id === props.cardId));
const canMoveUp = computed(() => !props.isReadOnly && position.value > 0);
const canMoveDown = computed(
    () => !props.isReadOnly && position.value !== -1 && position.value < cardsInList.value.length - 1,
);
const positionLabelId = useId();

const positionActions = computed(() => [
    { label: 'Move to top', icon: ArrowUpToLine, to: 0, isEnabled: canMoveUp.value },
    { label: 'Move up', icon: ArrowUp, to: position.value - 1, isEnabled: canMoveUp.value },
    { label: 'Move down', icon: ArrowDown, to: position.value + 1, isEnabled: canMoveDown.value },
    { label: 'Move to bottom', icon: ArrowDownToLine, to: cardsInList.value.length - 1, isEnabled: canMoveDown.value },
]);

/** The menu stays open so the card can be nudged again; focus leaves a button the move just disabled. */
async function reorder(to: number) {
    if (props.isReadOnly) {
        return;
    }

    const isMovingUp = to < position.value;
    emit('reorder', to);
    await nextTick();

    if (document.activeElement instanceof HTMLButtonElement && !document.activeElement.disabled) {
        return;
    }

    const enabledButtons = Array.from(positionGroup.value?.querySelectorAll('button:not(:disabled)') ?? []);
    const nextFocus = isMovingUp ? enabledButtons[0] : enabledButtons.at(-1);
    ((nextFocus as HTMLElement | undefined) ?? moveButton.value)?.focus();
}

const matchingLists = computed(() => {
    const search = query.value.trim().toLocaleLowerCase();

    return search
        ? props.boardLists.filter((list) => list.name.toLocaleLowerCase().includes(search))
        : props.boardLists;
});

function showMoveView() {
    if (!canMove.value) {
        return;
    }

    view.value = 'move';
}

async function showActionsView() {
    view.value = 'actions';
    query.value = '';
    await nextTick();
    moveButton.value?.focus();
}

function moveTo(list: BoardList) {
    isOpen.value = false;

    if (!props.isReadOnly && list.id !== props.currentListId) {
        emit('move', list.id);
    }
}

function requestDelete() {
    if (props.isReadOnly) {
        return;
    }

    isMovingFocus = true;
    isOpen.value = false;
    emit('delete');
}

function onCloseAutoFocus(event: Event) {
    if (isMovingFocus) {
        event.preventDefault();
        isMovingFocus = false;
    }
}

watch(query, async () => {
    await nextTick();
    listbox.value?.highlightFirstItem();
});

watch(isOpen, (open) => {
    if (!open) {
        view.value = 'actions';
        query.value = '';
    }
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                aria-label="Card actions"
                class="size-8 shrink-0 cursor-pointer text-muted-foreground hover:text-foreground data-[state=open]:bg-accent data-[state=open]:text-accent-foreground"
                size="icon"
                variant="ghost"
            >
                <Ellipsis />
            </Button>
        </PopoverTrigger>

        <PopoverContent
            :class="view === 'move' ? 'w-72' : 'w-52'"
            align="end"
            class="max-w-[calc(100vw-2rem)] overflow-hidden p-0"
            @close-auto-focus="onCloseAutoFocus"
        >
            <div v-if="view === 'actions'" class="p-1">
                <p class="px-2 py-1.5 text-sm font-medium">Card Actions</p>
                <Separator class="my-1" />
                <div ref="position-group" :aria-labelledby="positionLabelId" role="group">
                    <p :id="positionLabelId" class="px-2 pt-1 pb-1.5 text-xs text-muted-foreground tabular-nums">
                        <template v-if="position === -1">Position in list</template>
                        <template v-else>Position {{ position + 1 }} of {{ cardsInList.length }}</template>
                    </p>
                    <button
                        v-for="action in positionActions"
                        :key="action.label"
                        :disabled="!action.isEnabled"
                        class="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
                        type="button"
                        @click="reorder(action.to)"
                    >
                        <component :is="action.icon" aria-hidden="true" class="size-4 text-muted-foreground" />
                        {{ action.label }}
                    </button>
                </div>
                <Separator class="my-1" />
                <button
                    ref="move-button"
                    :disabled="!canMove"
                    class="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
                    type="button"
                    @click="showMoveView"
                >
                    <ArrowRightLeft aria-hidden="true" class="size-4 text-muted-foreground" />
                    <span class="flex-1">Move to list</span>
                    <ChevronRight aria-hidden="true" class="size-4 text-muted-foreground" />
                </button>
                <button
                    :disabled="isReadOnly"
                    class="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm text-destructive outline-none select-none hover:bg-destructive/10 focus-visible:bg-destructive/10 disabled:pointer-events-none disabled:opacity-50"
                    type="button"
                    @click="requestDelete"
                >
                    <Trash2 aria-hidden="true" class="size-4" />
                    Delete card
                </button>
            </div>

            <ListboxRoot
                v-else
                ref="listbox"
                :model-value="currentListId"
                aria-label="Move card to list"
                highlight-on-hover
            >
                <div class="flex items-center gap-1 border-b p-1">
                    <Button
                        aria-label="Back to card actions"
                        class="size-8 cursor-pointer"
                        size="icon"
                        variant="ghost"
                        @click="showActionsView"
                    >
                        <ArrowLeft />
                    </Button>
                    <p class="text-sm font-medium">Move to list</p>
                </div>
                <div class="flex items-center gap-2 border-b px-3">
                    <Search aria-hidden="true" class="size-4 shrink-0 text-muted-foreground" />
                    <ListboxFilter
                        v-model="query"
                        aria-label="Find a list"
                        auto-focus
                        class="h-10 min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground sm:text-sm"
                        placeholder="Find a list…"
                        @keydown.esc.stop.prevent="showActionsView"
                    />
                </div>

                <ListboxContent class="max-h-[min(18rem,50dvh)] overflow-y-auto overscroll-contain p-1">
                    <ListboxItem
                        v-for="list in matchingLists"
                        :key="list.id"
                        :value="list.id"
                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                        @select.prevent="moveTo(list)"
                    >
                        <span
                            :class="list.color ? `list-${list.color}` : 'list-default'"
                            aria-hidden="true"
                            class="size-2.5 shrink-0 rounded-full ring-1 ring-black/10 [background:var(--list-bg)] dark:ring-white/15"
                        />
                        <span :title="list.name" class="min-w-0 flex-1 truncate">{{ list.name }}</span>
                        <Check
                            v-if="list.id === currentListId"
                            aria-label="Current list"
                            class="size-4 shrink-0 text-primary"
                        />
                    </ListboxItem>

                    <p
                        v-if="!matchingLists.length"
                        class="px-2 py-6 text-center text-sm text-muted-foreground"
                        role="status"
                    >
                        No lists match “{{ query.trim() }}”.
                    </p>
                </ListboxContent>

                <p class="border-t px-3 py-2 text-xs text-muted-foreground">The card goes to the bottom of the list.</p>
            </ListboxRoot>
        </PopoverContent>
    </Popover>
</template>
