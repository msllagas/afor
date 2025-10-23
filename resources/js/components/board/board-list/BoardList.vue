<script lang="ts" setup>
import { computed, nextTick, ref, useTemplateRef } from 'vue'
import draggable from 'vuedraggable'
import { Form, router } from '@inertiajs/vue3'
import { X, Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import BoardListDropdownMenu from '@/components/board/board-list/BoardListDropdownMenu.vue'
import boardListRoutes from '@/routes/boards/board-lists'
import cardRoutes from '@/routes/board-lists/cards'
import CardController from '@/actions/App/Http/Controllers/CardController'
import type { Card as CardType, BoardList as BoardListType } from '@/types'

const props = defineProps<{
    boardList: BoardListType;
    isMovingBoardList: boolean;
}>();

const emit = defineEmits<{
    onCardClick: [boardListId: string, card: CardType]
}>()

const drag = ref(false);
const isAddingCard = ref(false);
const addCardInput = useTemplateRef('add-card-input')
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
}))

function onChange(boardListId: string, event: any) {
    if (event.moved) {
        handleCardMove(boardListId)
    }

    if (event.added) {
        handleCardMoveToBoardList(boardListId, event.added)
    }
}

function handleCardMove(boardListId: string) {
    props.boardList.cards.forEach((card, index) => {
        card.order = index;
    });

    router.patch(cardRoutes.reorder(boardListId).url, {
        cards: props.boardList.cards.map(c => ({
            id: c.id,
            order: c.order,
        })),
    }, {
        replace: true,
    });
}

function handleCardMoveToBoardList(boardListId: string, added: any) {
    if (!added?.element) return

    const card = added.element as CardType

    router.patch(cardRoutes.update({
        board_list: card.board_list_id,
        card: card.id,
    }).url, {
        board_list_id: boardListId,
        order: added.newIndex,
    })
}

function onArchiveList(boardId: string, boardListId: string) {
    router.patch(boardListRoutes.update({
        board: boardId,
        board_list: boardListId,
    }).url, {
        is_archived: true,
    })
}

async function onAddCard() {
    isAddingCard.value = true
    await nextTick()
    scrollToCard();
}

function scrollToCard() {
    addCardInput.value?.focus()

    const objDiv = document.getElementById(`board-${props.boardList.id}`)
    if (objDiv) {
        objDiv.scrollIntoView({ behavior: 'smooth' })
    }
}
</script>

<template>
    <Card
        class="flex relative flex-col space-between whitespace-normal scroll-m-2 bg-gray-950 pb-2 rounded-2xl shadow-lg w-[272px] max-h-full"
    >
        <CardHeader
            class="flex justify-between relative grow-0 flex-wrap items-start p-2 whitespace-normal handle cursor-grab">
            <div class="relative basis-[min-content] grow-1 shrink-1 min-h-[20px]">
                <h2 class="text-sm font-semibold">
                    <Button class="hover:!bg-transparent" size="sm" variant="ghost">
                        <span>{{ boardList.name }}</span>
                    </Button>
                </h2>
            </div>
            <BoardListDropdownMenu :board-list-id="boardList.id"
                                   @archive-list="onArchiveList"
            />
        </CardHeader>
        <CardContent class="p-2 pb-0 h-full overflow-y-auto overflow-x-hidden">
            <draggable
                :component-data="{
                                    tag: 'ol',
                                    type: 'transition-group',
                                    name: !drag ? 'flip-list' : null
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
                        <div :key="element.id"
                             :class="{ 'cursor-pointer': !drag }"
                             class="bg-[#242528] text-white p-2 mb-2 rounded-lg shadow"
                             @click="emit('onCardClick', boardList.id, element)"
                        >
                            <span class="text-sm break-words">{{ element.name }}</span>
                        </div>
                    </li>
                </template>
            </draggable>
            <div
                v-if="isAddingCard">
                <Form
                    v-slot="{ processing }"
                    class="space-y-6"
                    reset-on-success
                    v-bind="CardController.store.form(boardList.id)"
                    @success="scrollToCard"
                >
                    <Input id="name" ref="add-card-input" class="w-full p-2 mb-2 rounded-lg shadow" name="name"/>
                    <div class="flex gap-2 items-center">
                        <Button
                            :disabled="processing"
                            class="cursor-pointer"
                            data-test="update-profile-button"
                        >
                            Add Card
                        </Button>
                        <Button class="cursor-pointer" size="sm" variant="ghost" @click="isAddingCard = false">
                            <X/>
                        </Button>
                    </div>

                </Form>
            </div>
            <div :id="`board-${boardList.id}`"></div>
        </CardContent>
        <div v-if="!isAddingCard" class="px-2 pt-1.5">
            <Button class="w-full !justify-start hover:bg-[#242528]! cursor-pointer" size="sm"
                    variant="ghost"
                    @click="onAddCard"
            >
                <Plus/>
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

.handle, .handle * {
    cursor: grab;
}
</style>
