<script lang="ts" setup>
import CardActionsMenu from '@/components/board/board-list/card/CardActionsMenu.vue';
import Tiptap from '@/components/Tiptap.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { X } from 'lucide-vue-next';
import { useTextAreaAutoResize } from '@/composables/useTextAreaAutoResize';
import type { BoardList, Card } from '@/types';
import { nextTick, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    card: Card | null;
    /** The open card's description, which the board's cards leave out. Undefined while it loads. */
    description?: string | null;
    boardLists: BoardList[];
    boardName: string;
}>();

const emit = defineEmits<{
    close: [];
    rename: [card: Card, name: string];
    describe: [card: Card, description: string];
    move: [card: Card, boardListId: string];
    reorder: [card: Card, position: number];
    delete: [card: Card];
}>();

const { autoResize } = useTextAreaAutoResize();

// Keeps the last card on screen while the dialog animates closed.
const displayedCard = ref<Card | null>(props.card);
const displayedDescription = ref(props.description);
const draftName = ref(props.card?.name ?? '');
const isConfirmingDelete = ref(false);
const nameInput = useTemplateRef<HTMLTextAreaElement>('name-input');
const cancelDeleteButton = useTemplateRef<{ $el: HTMLElement }>('cancel-delete-button');

watch(
    () => props.card,
    async (card, previousCard) => {
        if (!card) {
            return;
        }

        displayedCard.value = card;

        if (card.id !== previousCard?.id) {
            isConfirmingDelete.value = false;
        }

        // Don't overwrite what the user is typing when the card refreshes from the server.
        if (document.activeElement !== nameInput.value) {
            draftName.value = card.name;
        }

        await nextTick();
        autoResize(nameInput.value);
    },
    { immediate: true },
);

watch(
    () => props.description,
    (description) => {
        if (props.card) {
            displayedDescription.value = description;
        }
    },
);

const listName = (card: Card) => props.boardLists.find((list) => list.id === card.board_list_id)?.name ?? 'a list';

function saveName(card: Card) {
    const name = draftName.value.trim();

    if (!name || name === card.name) {
        draftName.value = card.name;
        autoResize(nameInput.value);

        return;
    }

    emit('rename', card, name);
}

function cancelName(card: Card) {
    draftName.value = card.name;
    nameInput.value?.blur();
}

function saveDescription(card: Card, description: string) {
    if (description !== (displayedDescription.value ?? '')) {
        emit('describe', card, description);
    }
}

async function confirmDelete() {
    isConfirmingDelete.value = true;
    await nextTick();
    // Start on the safe choice.
    cancelDeleteButton.value?.$el.focus();
}

// Focus the dialog itself rather than the title field, so phones don't pop the keyboard open.
function focusDialog(event: Event) {
    event.preventDefault();
    (event.target as HTMLElement | null)?.focus({ preventScroll: true });
}

function onOpenChange(isOpen: boolean) {
    if (!isOpen) {
        emit('close');
    }
}
</script>

<template>
    <Dialog :open="!!card" @update:open="onOpenChange">
        <DialogContent
            class="flex max-h-[calc(100dvh-5rem)] flex-col gap-0 overflow-hidden p-0 outline-none max-sm:inset-0 max-sm:top-0 max-sm:left-0 max-sm:h-dvh max-sm:max-h-dvh max-sm:max-w-none max-sm:translate-x-0 max-sm:rounded-none max-sm:border-0 sm:max-w-2xl [&>button:last-child]:hidden"
            @open-auto-focus="focusDialog"
        >
            <template v-if="displayedCard">
                <header class="flex items-start gap-3 border-b py-4 pr-3 pl-5 sm:pr-4 sm:pl-6">
                    <div class="min-w-0 flex-1">
                        <DialogTitle class="sr-only">{{ displayedCard.name }}</DialogTitle>
                        <textarea
                            ref="name-input"
                            v-model="draftName"
                            aria-label="Card title"
                            class="-ml-2 block w-full resize-none overflow-hidden rounded-lg border border-transparent bg-transparent px-2 py-1 text-xl leading-snug font-semibold outline-none hover:bg-muted focus-visible:border-ring focus-visible:bg-background focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:text-2xl"
                            maxlength="255"
                            rows="1"
                            @blur="saveName(displayedCard)"
                            @input="autoResize"
                            @keydown.enter.prevent="($event.target as HTMLTextAreaElement).blur()"
                            @keydown.esc.stop.prevent="cancelName(displayedCard)"
                        />
                        <DialogDescription class="mt-1 text-sm text-muted-foreground">
                            In <span class="font-medium text-foreground">{{ listName(displayedCard) }}</span> on
                            {{ boardName }}
                        </DialogDescription>
                    </div>
                    <div class="mt-1.5 flex shrink-0 items-center gap-1">
                        <CardActionsMenu
                            :board-lists="boardLists"
                            :card-id="displayedCard.id"
                            :current-list-id="displayedCard.board_list_id"
                            @delete="confirmDelete"
                            @move="emit('move', displayedCard, $event)"
                            @reorder="emit('reorder', displayedCard, $event)"
                        />
                        <DialogClose as-child>
                            <Button
                                aria-label="Close"
                                class="size-8 cursor-pointer text-muted-foreground hover:text-foreground"
                                size="icon"
                                variant="ghost"
                            >
                                <X />
                            </Button>
                        </DialogClose>
                    </div>
                </header>

                <div
                    v-if="isConfirmingDelete"
                    class="flex flex-wrap items-center gap-2 border-b bg-destructive/10 px-5 py-3 sm:px-6"
                    role="alert"
                >
                    <p class="mr-auto text-sm">Delete this card? You can restore it from Archived items.</p>
                    <Button
                        ref="cancel-delete-button"
                        class="cursor-pointer"
                        size="sm"
                        variant="outline"
                        @click="isConfirmingDelete = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        class="cursor-pointer"
                        size="sm"
                        variant="destructive"
                        @click="emit('delete', displayedCard)"
                    >
                        Delete card
                    </Button>
                </div>

                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-5 py-5 sm:px-6">
                    <h3 class="text-sm font-medium">Description</h3>
                    <div
                        v-if="displayedDescription === undefined"
                        aria-busy="true"
                        aria-label="Loading description"
                        class="min-h-42 space-y-2.5 rounded-xl border px-4 py-3.5"
                    >
                        <Skeleton class="h-4 w-3/4" />
                        <Skeleton class="h-4 w-1/2" />
                        <Skeleton class="h-4 w-2/3" />
                    </div>
                    <div
                        v-else
                        class="w-full overflow-clip rounded-xl border border-input bg-muted/30 shadow-xs transition-[color,box-shadow] focus-within:border-primary/60 focus-within:ring-[3px] focus-within:ring-primary/20 dark:bg-input/20"
                    >
                        <Tiptap
                            :key="displayedCard.id"
                            :model-value="displayedDescription ?? ''"
                            name="description"
                            @blur="saveDescription(displayedCard, $event)"
                        />
                    </div>
                </div>
            </template>
        </DialogContent>
    </Dialog>
</template>
