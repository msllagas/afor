<script lang="ts" setup>
import Tiptap from '@/components/Tiptap.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Textarea } from '@/components/ui/textarea';
import { useTextAreaAutoResize } from '@/composables/useTextAreaAutoResize';
import cardRoutes from '@/routes/board-lists/cards';
import type { Card } from '@/types';
import { router } from '@inertiajs/vue3';
import { MoreHorizontal, Trash2 } from 'lucide-vue-next';

defineProps<{
    selectedCard?: Card | null;
    isFetching: boolean;
}>();

const emit = defineEmits<{
    updateOpen: [value: boolean];
    deleteCard: [boardListId: string, cardId: string];
}>();

const isDialogOpen = defineModel<boolean>({ required: true });

const { autoResize } = useTextAreaAutoResize();

function onSubmit(boardListId: string, cardId: string, event: Event) {
    const target = event.target;
    if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) return;

    const name = target.name;
    const value = target.value;

    router.patch(
        cardRoutes.update({
            board_list: boardListId,
            card: cardId,
        }).url,
        {
            [name]: value,
        },
    );
}

function onSubmitDescription(boardListId: string, cardId: string, value: string) {
    router.patch(
        cardRoutes.update({
            board_list: boardListId,
            card: cardId,
        }).url,
        {
            description: value,
        },
    );
}
</script>

<template>
    <Dialog v-model:open="isDialogOpen" @update:open="(value: boolean) => emit('updateOpen', value)">
        <DialogContent
            :class="{ '[&>button:first-of-type]:hidden': isFetching }"
            class="max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto] sm:max-w-[600px]"
        >
            <DialogHeader>
                <DialogTitle></DialogTitle>
                <DialogDescription> </DialogDescription>
            </DialogHeader>
            <div v-if="isFetching" class="[&>button:first-of-type]:hidden"></div>
            <div v-else-if="!isFetching && selectedCard" class="overflow-y-auto">
                <div class="space-y-6">
                    <div class="grid gap-4 overflow-y-auto py-4">
                        <div class="flex items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <h2 class="sr-only">{{ selectedCard.name }}</h2>
                                <Textarea
                                    id="name"
                                    :model-value="selectedCard.name"
                                    class="w-full resize-none overflow-hidden border-none p-0 text-2xl leading-tight font-semibold focus:ring-0 focus:outline-none"
                                    name="name"
                                    rows="1"
                                    un-styled
                                    @blur="onSubmit(selectedCard.board_list_id, selectedCard.id, $event)"
                                    @focus="autoResize"
                                    @input="autoResize"
                                    @keydown.enter.prevent="$event.target.blur()"
                                    @keydown.esc.prevent="$event.target.blur()"
                                />
                            </div>

                            <!-- Dropdown menu -->
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    aria-label="Card options"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end" class="w-40">
                                    <DropdownMenuLabel>Card Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive"
                                        @click="emit('deleteCard', selectedCard.board_list_id, selectedCard.id)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete card
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                    <div class="space-y-4 py-2">
                        <h3 class="text-sm font-medium">Description</h3>
                        <div class="w-full rounded-lg border transition-colors focus-within:border-pink-100">
                            <Tiptap
                                :model-value="selectedCard.description"
                                name="description"
                                @blur="onSubmitDescription(selectedCard.board_list_id, selectedCard.id, $event)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
