<script lang="ts" setup>
import CardController from '@/actions/App/Http/Controllers/CardController';
import BoardListDropdownMenu from '@/components/board/board-list/BoardListDropdownMenu.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTextAreaAutoResize } from '@/composables/useTextAreaAutoResize';
import type { BoardList, Card, SortableChangeEvent } from '@/types';
import { Form } from '@inertiajs/vue3';
import { AlignLeft, Plus, X } from 'lucide-vue-next';
import { nextTick, ref, useTemplateRef, watch } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps<{
    boardList: BoardList;
    colors: Array<string>;
    cardDragOptions: Record<string, unknown>;
    canMoveLeft: boolean;
    canMoveRight: boolean;
    /** A card (not a list) is being dragged somewhere on the board. */
    isDraggingCard: boolean;
}>();

const emit = defineEmits<{
    openCard: [card: Card];
    cardsChange: [boardList: BoardList, event: SortableChangeEvent<Card>];
    dragStart: [];
    dragEnd: [];
    rename: [name: string];
    recolor: [color: string | null];
    move: [direction: -1 | 1];
    archive: [];
    requestFailed: [message: string, status?: number];
}>();

const { autoResize } = useTextAreaAutoResize();

const isEditingName = ref(false);
const draftName = ref(props.boardList.name);
const nameInput = useTemplateRef<HTMLTextAreaElement>('name-input');
const nameButton = useTemplateRef<HTMLButtonElement>('name-button');

const isAddingCard = ref(false);
const cardInput = useTemplateRef<HTMLTextAreaElement>('card-input');
const cardScroller = useTemplateRef<HTMLElement>('card-scroller');

watch(
    () => props.boardList.name,
    (name) => {
        if (!isEditingName.value) {
            draftName.value = name;
        }
    },
);

async function startEditingName() {
    draftName.value = props.boardList.name;
    isEditingName.value = true;
    await nextTick();
    autoResize(nameInput.value);
    nameInput.value?.select();
}

/** Keyboard saves and cancels hand focus back to the name; a blur leaves it where the user clicked. */
async function stopEditingName(shouldRestoreFocus: boolean) {
    isEditingName.value = false;

    if (shouldRestoreFocus) {
        await nextTick();
        nameButton.value?.focus();
    }
}

function saveName(shouldRestoreFocus = false) {
    if (!isEditingName.value) {
        return;
    }

    stopEditingName(shouldRestoreFocus);
    const name = draftName.value.trim();

    if (!name || name === props.boardList.name) {
        draftName.value = props.boardList.name;

        return;
    }

    emit('rename', name);
}

function cancelEditingName() {
    draftName.value = props.boardList.name;
    stopEditingName(true);
}

async function openCardComposer() {
    isAddingCard.value = true;
    await nextTick();
    focusCardComposer();
}

function focusCardComposer() {
    cardInput.value?.focus({ preventScroll: true });
    cardInput.value?.scrollIntoView({ block: 'nearest' });
}

function closeCardComposer() {
    isAddingCard.value = false;
}

function onCardComposerBlur(event: FocusEvent) {
    const nextFocus = event.relatedTarget as Node | null;
    const composer = (event.currentTarget as HTMLElement).closest('form');

    // Stay open while focus moves to the form's own buttons, or when there's a draft to keep.
    if (composer?.contains(nextFocus) || cardInput.value?.value.trim()) {
        return;
    }

    closeCardComposer();
}

function submitOnEnter(event: KeyboardEvent) {
    // Let IME composition (e.g. Japanese input) confirm with Enter without submitting.
    if (event.isComposing) {
        return;
    }

    (event.target as HTMLTextAreaElement).form?.requestSubmit();
}

async function onCardAdded() {
    await nextTick();
    autoResize(cardInput.value);
    focusCardComposer();
    cardScroller.value?.scrollTo({ top: cardScroller.value.scrollHeight });
}

function onRequestFailed(response?: { status: number }) {
    emit('requestFailed', 'Could not add the card. Check your connection and try again.', response?.status);

    return false;
}
</script>

<template>
    <section
        :aria-label="`List ${boardList.name}`"
        :class="boardList.color ? `list-${boardList.color}` : 'list-default'"
        class="flex min-h-0 w-full flex-col rounded-2xl text-(--list-fg) shadow-sm ring-1 ring-black/5 [background:var(--list-bg)] dark:ring-white/5"
    >
        <header class="flex cursor-grab items-start gap-1 px-2 pt-2 pb-1 active:cursor-grabbing" data-list-handle>
            <div class="min-w-0 flex-1">
                <h2 v-if="!isEditingName">
                    <button
                        class="w-full cursor-pointer rounded-lg px-2 py-1.5 text-left text-sm leading-5 font-semibold break-words transition-colors outline-none hover:bg-(--list-bg-hovered) focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        title="Rename list"
                        type="button"
                        ref="name-button"
                        @click="startEditingName"
                    >
                        {{ boardList.name }}
                    </button>
                </h2>
                <textarea
                    v-else
                    ref="name-input"
                    v-model="draftName"
                    aria-label="List name"
                    class="block w-full resize-none overflow-hidden rounded-lg border border-input bg-background px-2 py-1.5 text-base leading-5 font-semibold text-foreground outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:text-sm"
                    maxlength="255"
                    rows="1"
                    @blur="saveName()"
                    @input="autoResize"
                    @keydown.enter.prevent="saveName(true)"
                    @keydown.esc.stop.prevent="cancelEditingName"
                />
            </div>
            <span
                :aria-label="`${boardList.cards.length} ${boardList.cards.length === 1 ? 'card' : 'cards'}`"
                class="mt-1.5 shrink-0 rounded-full bg-(--list-bg-hovered) px-2 py-0.5 text-xs font-medium text-(--list-fg-muted) tabular-nums"
                role="img"
            >
                {{ boardList.cards.length }}
            </span>
            <BoardListDropdownMenu
                :can-move-left="canMoveLeft"
                :can-move-right="canMoveRight"
                :color="boardList.color ?? null"
                :colors="colors"
                :list-name="boardList.name"
                class="text-(--list-fg-muted) hover:bg-(--list-bg-hovered)! hover:text-(--list-fg)!"
                @add-card="openCardComposer"
                @archive-list="emit('archive')"
                @color-selected="emit('recolor', $event)"
                @move="emit('move', $event)"
            />
        </header>

        <div
            ref="card-scroller"
            data-card-scroller
            class="relative min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain px-2 pt-1 pb-2"
        >
            <draggable
                :class="{ 'min-h-16': !boardList.cards.length && (isDraggingCard || !isAddingCard) }"
                :list="boardList.cards"
                class="peer flex flex-col gap-2"
                item-key="id"
                tag="ol"
                v-bind="cardDragOptions"
                @change="emit('cardsChange', boardList, $event)"
                @end="emit('dragEnd')"
                @start="emit('dragStart')"
            >
                <template #item="{ element }">
                    <li
                        :data-card-id="element.id"
                        class="board-card list-none select-none [-webkit-touch-callout:none]"
                    >
                        <button
                            class="w-full cursor-pointer rounded-xl border border-border/60 bg-card px-3 py-2.5 text-left text-sm text-card-foreground shadow-xs transition-[border-color,box-shadow] outline-none hover:border-primary/40 hover:shadow-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            type="button"
                            @click="emit('openCard', element)"
                        >
                            <span class="block break-words">{{ element.name }}</span>
                            <span
                                v-if="element.has_description"
                                class="mt-1.5 flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <AlignLeft aria-hidden="true" class="size-3.5" />
                                <span class="sr-only">Has a description</span>
                            </span>
                        </button>
                    </li>
                </template>
            </draggable>
            <!-- While a card is dragged, empty lists become drop zones; the hint steps aside once the card is over it. -->
            <p
                v-if="!boardList.cards.length && (isDraggingCard || !isAddingCard)"
                :class="
                    isDraggingCard
                        ? 'border-2 border-current/40 bg-(--list-bg-hovered) font-medium text-(--list-fg)'
                        : 'border border-current/25 text-(--list-fg-muted)'
                "
                class="pointer-events-none absolute inset-x-2 top-1 flex h-16 items-center justify-center rounded-xl border-dashed text-xs transition-colors peer-has-[.board-drag-ghost]:hidden"
            >
                {{ isDraggingCard ? 'Drop a card here' : 'No cards yet' }}
            </p>

            <Form
                v-if="isAddingCard"
                v-slot="{ errors, processing }"
                :options="{ preserveScroll: true, preserveState: true, only: ['board'] }"
                :class="boardList.cards.length ? 'mt-2' : 'mt-1'"
                class="space-y-2"
                reset-on-success
                v-bind="CardController.store.form(boardList.id)"
                @http-exception="onRequestFailed"
                @network-error="onRequestFailed()"
                @success="onCardAdded"
            >
                <textarea
                    ref="card-input"
                    :aria-invalid="!!errors.name"
                    aria-label="Card title"
                    autocomplete="off"
                    class="block w-full resize-none overflow-hidden rounded-xl border border-input bg-card px-3 py-2.5 text-base text-card-foreground shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive sm:text-sm"
                    maxlength="255"
                    name="name"
                    placeholder="Enter a title for this card"
                    required
                    rows="2"
                    @blur="onCardComposerBlur"
                    @input="autoResize"
                    @keydown.enter.exact.prevent="submitOnEnter"
                    @keydown.esc.stop.prevent="closeCardComposer"
                />
                <InputError :message="errors.name" />
                <div class="flex items-center gap-1.5">
                    <Button :disabled="processing" class="cursor-pointer" size="sm" type="submit">
                        {{ processing ? 'Adding…' : 'Add card' }}
                    </Button>
                    <Button
                        aria-label="Stop adding cards"
                        class="size-8 cursor-pointer text-(--list-fg-muted) hover:bg-(--list-bg-hovered)! hover:text-(--list-fg)!"
                        size="icon"
                        type="button"
                        variant="ghost"
                        @click="closeCardComposer"
                    >
                        <X />
                    </Button>
                </div>
            </Form>
        </div>

        <div v-if="!isAddingCard" class="px-2 pb-2">
            <Button
                class="h-9 w-full cursor-pointer justify-start gap-2 rounded-lg px-2 text-(--list-fg-muted) hover:bg-(--list-bg-hovered)! hover:text-(--list-fg)!"
                type="button"
                variant="ghost"
                @click="openCardComposer"
            >
                <Plus />
                Add a card
            </Button>
        </div>
    </section>
</template>
