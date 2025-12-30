<script lang="ts" setup>
import CardController from '@/actions/App/Http/Controllers/CardController';
import BoardListDropdownMenu from '@/components/board/board-list/BoardListDropdownMenu.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import cardRoutes from '@/routes/board-lists/cards';
import boardListRoutes from '@/routes/boards/board-lists';
import type { BoardList as BoardListType, Card as CardType } from '@/types';
import { Form, router } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps<{
    boardList: BoardListType;
    isMovingBoardList: boolean;
}>();

const emit = defineEmits<{
    onCardClick: [boardListId: string, card: CardType];
}>();

const drag = ref(false);
const isAddingCard = ref(false);
const addCardInput = useTemplateRef('add-card-input');
const dragOptions = computed(() => ({
    animation: 200,
    group: {
        name: 'boardCard',
        pull: true,
        put: !props.isMovingBoardList,
    },
    ghostClass: 'ghost',
    dragClass: 'drag',
    forceFallback: true,
    disabled: false,
    scrollSensitivity: 100,
    scrollSpeed: 20,
}));

function onChange(boardListId: string, event: any) {
    if (event.moved) {
        handleCardMove(boardListId);
    }

    if (event.added) {
        handleCardMoveToBoardList(boardListId, event.added);
    }
}

function handleCardMove(boardListId: string) {
    props.boardList.cards.forEach((card, index) => {
        card.order = index;
    });

    router.patch(
        cardRoutes.reorder(boardListId).url,
        {
            cards: props.boardList.cards.map((c) => ({
                id: c.id,
                order: c.order,
            })),
        },
        {
            replace: true,
        },
    );
}

function handleCardMoveToBoardList(boardListId: string, added: any) {
    if (!added?.element) return;

    const card = added.element as CardType;

    router.patch(
        cardRoutes.update({
            board_list: card.board_list_id,
            card: card.id,
        }).url,
        {
            board_list_id: boardListId,
            order: added.newIndex,
        },
    );
}

function onArchiveList(boardId: string, boardListId: string) {
    router.patch(
        boardListRoutes.update({
            board: boardId,
            board_list: boardListId,
        }).url,
        {
            is_archived: true,
        },
    );
}

async function onAddCard() {
    isAddingCard.value = true;
    await nextTick();
    scrollToCard();
}

function scrollToCard() {
    addCardInput.value?.focus();

    const objDiv = document.getElementById(`board-${props.boardList.id}`);
    if (objDiv) {
        objDiv.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<template>
    <Card
        class="space-between relative flex max-h-full w-[272px] scroll-m-2 flex-col rounded-2xl bg-gray-950 pb-2 whitespace-normal shadow-lg"
    >
        <CardHeader
            class="handle relative flex grow-0 cursor-grab flex-wrap items-start justify-between p-2 whitespace-normal"
        >
            <div class="relative min-h-[20px] shrink-1 grow-1 basis-[min-content]">
                <h2 class="text-sm font-semibold">
                    <Button class="hover:!bg-transparent" size="sm" variant="ghost">
                        <span>{{ boardList.name }}</span>
                    </Button>
                </h2>
            </div>
            <BoardListDropdownMenu :board-list-id="boardList.id" @archive-list="onArchiveList" />
        </CardHeader>
        <CardContent class="h-full overflow-x-hidden overflow-y-auto p-2 pb-0">
            <draggable
                :component-data="{
                    tag: 'ol',
                    type: 'transition-group',
                    name: !drag ? 'flip-list' : null,
                }"
                :list="boardList.cards"
                item-key="id"
                v-bind="dragOptions"
                @change="onChange(boardList.id, $event)"
                @end="drag = false"
                @start="drag = true"
            >
                <template #item="{ element }">
                    <li>
                        <div
                            :key="element.id"
                            :class="{ 'cursor-pointer': !drag }"
                            class="mb-2 rounded-lg bg-[#242528] p-2 text-white shadow"
                            @click="emit('onCardClick', boardList.id, element)"
                        >
                            <span class="text-sm break-words">{{ element.name }}</span>
                        </div>
                    </li>
                </template>
            </draggable>
            <div v-if="isAddingCard">
                <Form
                    v-slot="{ processing }"
                    class="space-y-6"
                    reset-on-success
                    v-bind="CardController.store.form(boardList.id)"
                    @success="scrollToCard"
                >
                    <Input id="name" ref="add-card-input" class="mb-2 w-full rounded-lg p-2 shadow" name="name" />
                    <div class="flex items-center gap-2">
                        <Button :disabled="processing" class="cursor-pointer" data-test="update-profile-button">
                            Add card
                        </Button>
                        <Button class="cursor-pointer" size="sm" variant="ghost" @click="isAddingCard = false">
                            <X />
                        </Button>
                    </div>
                </Form>
            </div>
            <div :id="`board-${boardList.id}`"></div>
        </CardContent>
        <div v-if="!isAddingCard" class="px-2 pt-1.5">
            <Button
                class="w-full cursor-pointer !justify-start hover:bg-[#242528]!"
                size="sm"
                variant="ghost"
                @click="onAddCard"
            >
                <Plus />
                <span>Add card</span>
            </Button>
        </div>
    </Card>
</template>

<style scoped>
.ghost {
    background: violet !important;
    border-radius: 8px;
    opacity: 1 !important;
    transition: all 0.15s ease;
}

.ghost > div {
    visibility: hidden;
}

.drag {
    transform: rotate(5deg);
}

.handle,
.handle * {
    cursor: grab;
}
</style>
