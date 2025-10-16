<script setup lang="ts">
import {computed, nextTick, onMounted, ref, useTemplateRef} from "vue"
import {Form, Head, router} from "@inertiajs/vue3"
import draggable from "vuedraggable"
import {Ellipsis, Plus} from "lucide-vue-next"
import {Button} from "@/components/ui/button"
import {Card, CardContent, CardHeader} from "@/components/ui/card"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import {Input} from "@/components/ui/input"
import cardRoutes from "@/routes/board-lists/cards"
import boardRoutes from "@/routes/boards"
import CardController from "@/actions/App/Http/Controllers/CardController"

interface Card {
    id: string;
    name: string;
    description?: string;
    order: number;
    board_list_id: string;
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

const props = defineProps<{
    board: Board;
    card?: Card;
}>();

const drag = ref(false)
const isDialogOpen = ref(false)
const isFetching = ref(false)

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

function onDragEnd() {
    drag.value = false
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

function onChange(boardListId: string, event: any) {
    if (event.moved) {
        handleCardMove(boardListId)
    }

    if (event.added) {
        handleCardMoveToBoardList(boardListId, event.added)
    }
}

function handleCardMove(boardListId: string) {
    const boardList = props.board.board_lists.find(list => list.id === boardListId);
    if (!boardList) return;

    boardList.cards.forEach((card, index) => {
        card.order = index;
    });

    router.patch(cardRoutes.reorder(boardListId).url, {
        cards: boardList.cards.map(c => ({
            id: c.id,
            order: c.order,
        })),
    });
}

function handleCardMoveToBoardList(boardListId: string, added: any) {
    if (!added?.element) return

    const card = added.element as Card

    router.patch(cardRoutes.update({
        board_list: card.board_list_id,
        card: card.id,
    }).url, {
        board_list_id: boardListId,
        order: added.newIndex,
    })
}

function onCardClick(boardListId: string, card: Card) {
    isFetching.value = true
    isDialogOpen.value = true

    const url = cardRoutes.edit({
        board_list: boardListId,
        card: card.id,
    }).url

    window.history.pushState({}, '', url)

    router.visit(url, {
        only: ['card'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            isFetching.value = false
        },
    })
}

function onDialogClose(value: boolean) {
    if (!value) {
        window.history.replaceState({}, '', boardRoutes.show(props.board.id).url)

        router.visit(boardRoutes.show(props.board.id).url, {
            only: ['board', 'boardLists'],
            preserveScroll: true,
            preserveState: true,
        })
    }
}

function autoResize(event: Event) {
    const target = event.target as HTMLTextAreaElement | null
    if (!target) return

    target.style.height = 'auto'
    target.style.height = `${target.scrollHeight}px`
}

function onSubmit(boardListId: string, card: Card, event: Event) {
    const target = event.target
    if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) return

    const name = target.name
    const value = target.value

    router.patch(cardRoutes.update({board_list: boardListId, card: card.id}).url, {
        [name]: value,
    })
}

onMounted(() => {
    if (props.card) {
        isDialogOpen.value = true
    }
});

</script>

<template>
    <Head :title="board?.name ?? card.name"/>
    <div
        class="relative bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 h-screen overflow-y-auto select-none">
        <ol v-if="board?.board_lists?.length > 0"
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
                                @end="onDragEnd"
                                @change="onChange(boardList.id, $event)"
                                v-bind="dragOptions"
                                group="boardCard"
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
                                             @click="onCardClick(boardList.id, element)"
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
    <Dialog v-model:open="isDialogOpen" @update:open="(value:boolean) => onDialogClose(value)">
        <DialogContent v-if="isFetching"
                       class="[&>button:first-of-type]:hidden ">
            <DialogTitle hidden>Loading</DialogTitle>
            <DialogDescription hidden>
                Loading...
            </DialogDescription>
        </DialogContent>
        <DialogContent class="sm:max-w-[600px] max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto]" v-else-if="!isFetching && card">
            <DialogHeader>
                <DialogTitle></DialogTitle>
                <DialogDescription>
                </DialogDescription>
            </DialogHeader>
            <div class="overflow-y-auto">
                <Form
                    class="space-y-6"
                >
                    <div class="grid gap-4 py-4 overflow-y-auto">
                        <div>
                            <h2 class="sr-only">{{ card.name }}</h2>
                            <div>
                                <h2 class="sr-only">{{ card.name }}</h2>
                                <Textarea
                                    id="name"
                                    name="name"
                                    :value="card.name"
                                    class="w-full text-2xl font-semibold leading-tight resize-none overflow-hidden border-none focus:ring-0 focus:outline-none p-0"
                                    rows="1"
                                    @input="autoResize($event)"
                                    @focus="autoResize($event)"
                                    @blur="onSubmit(card.board_list_id, card, $event)"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4 py-2">
                        <h3 class="text-sm font-medium">Description</h3>
                        <Textarea
                            id="description"
                            name="description"
                            :value="card.description"
                            class="w-full text-sm resize-none border border-gray-300 rounded-md p-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            rows="1"
                            @input="autoResize($event)"
                            @focus="autoResize($event)"
                            @blur="onSubmit(card.board_list_id, card, $event)"
                        />

                    </div>
                </Form>

            </div>
        </DialogContent>
    </Dialog>
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
