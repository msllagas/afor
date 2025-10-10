<script setup lang="ts">
import {computed, ref} from "vue"
import {Head, router} from "@inertiajs/vue3"
import draggable from "vuedraggable"
import {Plus, Ellipsis} from "lucide-vue-next"
import {Button} from "@/components/ui/button"
import {Card, CardContent, CardFooter, CardHeader} from "@/components/ui/card"
import {cn} from "@/lib/utils"
import cards from "@/routes/board-lists/cards"

interface Card {
    id: string;
    name: string;
    description?: string;
    order: number;
}

interface BoardList {
    id: string;
    name: string;
    cards: Card[];
}

interface Board {
    id: string;
    name: string;
    board_lists: BoardList[];
}

defineProps<{
    board: Board;
}>();

const drag = ref(false)
const dragOptions = computed(() => ({
    animation: 200,
    group: 'description',
    disabled: false,
    ghostClass: 'ghost',
}))

function onDragEnd(boardList: BoardList) {
    drag.value = false

    const changedCards: { id: string; order: number }[] = []

    boardList.cards.forEach((card, index) => {
        const newOrder = index + 1
        if (card.order !== newOrder) {
            card.order = newOrder
            changedCards.push({id: card.id, order: newOrder})
        }
    })

    if (changedCards.length === 0) return

    router.patch(cards.reorder(boardList.id).url, {
        cards: changedCards
    })
}
</script>

<template>
    <Head :title="board.name"/>
    <div class="relative bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 h-screen overflow-y-auto">
        <ol v-if="board?.board_lists.length > 0"
            class="h-full p-2 flex overflow-hidden absolute scrollbar-color-white scrollbar-thin scrollbar-track-transparent scrollbar-thumb-white/20">
            <template v-for="boardList in board.board_lists" :key="boardList.id">
                <li class="h-full w-[320px] p-2">
                    <Card :class="cn('w-full p-2 pt-1 gap-0 bg-gray-950', $attrs.class ?? '')">
                        <CardHeader class="relative flex items-center justify-between p-2 pt-1">
                            <div class="px-2.5">
                                <h2 class="text-sm font-semibold">{{ boardList.name }}</h2>
                            </div>
                            <Button variant="ghost" size="sm">
                                <Ellipsis/>
                            </Button>
                        </CardHeader>
                        <CardContent class="grid gap-4 p-0">
                            <div>
                                <draggable
                                    v-model="boardList.cards"
                                    item-key="id"
                                    @start="drag = true"
                                    @end="onDragEnd(boardList)"
                                    v-bind="dragOptions"
                                    :component-data="{
                                    tag: 'ul',
                                    type: 'transition-group',
                                    name: !drag ? 'flip-list' : null
                                }"
                                >
                                    <template #item="{ element }">
                                        <li class="bg-[#242528] text-white p-2 mb-2 rounded-lg shadow"
                                            :class="{ 'cursor-pointer': !drag }"
                                            @click="() => console.log(boardList.cards)"
                                            :key="element.id"
                                        >
                                            <span class="text-sm">{{ element.name }}</span>
                                        </li>
                                    </template>
                                </draggable>
                            </div>
                        </CardContent>
                        <CardFooter class="p-2 hover:bg-[#242528] rounded-lg cursor-pointer">
                            <div class="flex items-center gap-2 text-sm font-semibold text-white">
                                <Plus/>
                                <span class="text-gray-400">Add a Card</span>
                            </div>
                        </CardFooter>
                    </Card>
                </li>
            </template>
        </ol>
    </div>
</template>

<style scoped>
</style>
