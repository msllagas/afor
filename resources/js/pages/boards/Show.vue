<script setup lang="ts">
import {computed, nextTick, ref, useTemplateRef} from "vue"
import {Form, Head, router} from "@inertiajs/vue3"
import draggable from "vuedraggable"
import {Ellipsis, Plus} from "lucide-vue-next"
import {Button} from "@/components/ui/button"
import {Card, CardContent, CardHeader} from "@/components/ui/card"
import {Input} from "@/components/ui/input"
import cards from "@/routes/board-lists/cards"
import CardController from "@/actions/App/Http/Controllers/CardController"

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
    group: {
        name: 'cards',
        pull: true,
        put: true,
    },
    ghostClass: 'ghost',
    dragClass: 'drag',
    forceFallback: true,
    disabled: false,
    scrollSensitivity: 100,
    scrollSpeed: 20,
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

const itemRef = useTemplateRef<HTMLInputElement[]>('items')
const activeBoardList = ref<number | null>(null)

function onDragStart() {
    drag.value = true
}

async function onAddCard(index: number) {
    activeBoardList.value = index

    await nextTick();
    const inputs = itemRef.value
    if (inputs && inputs[index]) {
        inputs[index].focus()
    }
}

async function createCard(index: number) {
    await nextTick();
    const inputs = itemRef.value
    if (inputs && inputs[index]) {
        inputs[index].focus()
    }
    const objDiv = document.getElementById(`board-${index}`)
    if (objDiv) {
        objDiv.scrollIntoView({behavior: 'smooth'})
    }
}
</script>

<template>
    <Head :title="board.name"/>
    <div
        class="relative bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 h-screen overflow-y-auto select-none">
        <ol v-if="board?.board_lists.length > 0"
            class="h-full p-2 flex overflow-hidden absolute pb-32 gap-2">
            <template v-for="(boardList, index) in board.board_lists" :key="boardList.id">
                <li class="h-full whitespace-nowrap block shrink-0 self-start">
                    <Card
                        class="flex relative flex-col space-between whitespace-normal scroll-m-2 bg-gray-950 pb-2 rounded-lg shadow-lg w-[272px] max-h-full">
                        <CardHeader
                            class="flex justify-between relative grow-0 flex-wrap items-start p-2 whitespace-normal">
                            <div class="relative basis-[min-content] grow-1 shrink-1 min-h-[20px]">
                                <h2 class="text-sm font-semibold ">
                                    <Button variant="ghost" size="sm" class="hover:!bg-transparent">
                                        <span>{{ boardList.name }}</span>
                                    </Button>
                                </h2>
                            </div>
                            <Button variant="ghost" size="sm">
                                <Ellipsis/>
                            </Button>
                        </CardHeader>
                        <CardContent class="p-2 h-full overflow-y-auto overflow-x-hidden">
                            <draggable
                                v-model="boardList.cards"
                                item-key="id"
                                @start="onDragStart"
                                @end="onDragEnd(boardList)"
                                v-bind="dragOptions"
                                :component-data="{
                                    tag: 'ol',
                                    type: 'transition-group',
                                    name: !drag ? 'flip-list' : null
                                }"
                            >
                                <template #item="{ element }">
                                    <li>
                                        <div class="bg-[#242528] text-white p-2 mb-2 rounded-lg shadow"
                                             :class="{ 'cursor-pointer': !drag }"
                                             @click="() => console.log(boardList.cards)"
                                             :key="element.id"
                                        >
                                            <span class="text-sm">{{ element.name }}</span>
                                        </div>
                                    </li>
                                </template>
                            </draggable>
                            <div>
                                <Form
                                    v-bind="CardController.store.form(boardList.id)"
                                    @success="createCard(index)"
                                    reset-on-success
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                    v-if="activeBoardList === index"
                                >
                                    <Input id="name" name="name" class="w-full p-2 mb-2 rounded-lg shadow" ref="items"/>
                                    <Button
                                        :disabled="processing"
                                        data-test="update-profile-button"
                                    >
                                        Add Card
                                    </Button
                                    >
                                </Form>
                            </div>
                            <div :id="`board-${index}`"></div>
                        </CardContent>

                        <div class="px-2 pt-1.5">
                            <Button variant="ghost" size="sm"
                                    class="w-full !justify-start hover:bg-[#242528]! cursor-pointer"
                                    @click="onAddCard(index)"
                            >
                                <Plus/>
                                <span>Add card</span>
                            </Button>
                        </div>
                    </Card>
                </li>
            </template>
        </ol>
    </div>
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

</style>
