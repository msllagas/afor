<script lang="ts" setup>
import { onMounted, provide, ref } from 'vue'
import { Form, Head, router } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import BoardList from '@/components/board/board-list/BoardList.vue'
import CardDialog from '@/components/board/board-list/card/CardDialog.vue'
import boardRoutes from '@/routes/boards'
import cardRoutes from '@/routes/board-lists/cards'
import BoardListController from '@/actions/App/Http/Controllers/BoardListController'
import type { Board, Card } from '@/types'

const props = defineProps<{
    board: Board;
    selectedCard?: Card;
}>();

const isDialogOpen = ref(false)
const isFetching = ref(false)
const isAddingNewBoardList = ref(false);
const headerTitle = ref(props.board.name)

function onCardClick(boardListId: string, card: Card) {
    isFetching.value = true
    isDialogOpen.value = true

    const url = cardRoutes.show({
        board_list: boardListId,
        card: card.id,
    }).url
    headerTitle.value = card.name

    window.history.pushState({}, '', url)

    router.visit(url, {
        only: ['selectedCard'],
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
        headerTitle.value = props.board.name
        isDialogOpen.value = false

        window.history.pushState({}, '', boardRoutes.show(props.board.id).url)
        router.visit(boardRoutes.show(props.board.id).url, {
            only: ['board', 'selectedCard'],
            preserveScroll: true,
            preserveState: true,
        })
    }
}

provide('boardId', props.board.id)

onMounted(() => {
    if (props.selectedCard) {
        isDialogOpen.value = true
    }
});

</script>

<template>
    <Head :title="headerTitle"/>
    <div
        class="relative bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 h-screen overflow-y-auto select-none">
        <ol v-if="board?.board_lists?.length > 0"
            class="h-full p-2 flex overflow-hidden absolute pb-32 gap-2">
            <template v-for="(boardList, index) in board.board_lists" :key="boardList.id">
                <li class="h-full whitespace-nowrap block shrink-0 self-start">
                    <BoardList :key="index"
                               :board-list="boardList"
                               @on-card-click="onCardClick"
                    />
                </li>
            </template>
            <li v-if="isAddingNewBoardList" class="h-full whitespace-nowrap block shrink-0 self-start rounded-lg">
                <div class="w-[272px] bg-black rounded-lg p-2">
                    <Form
                        v-slot="{ processing }"
                        class="space-y-6"
                        reset-on-success
                        v-bind="BoardListController.store.form(board.id)"
                    >
                        <Input id="name" class="w-full p-2 mb-2 rounded-lg shadow" name="name"/>
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            Add Card
                        </Button
                        >
                    </Form>
                </div>
            </li>
            <li v-else class="h-full whitespace-nowrap block shrink-0 self-start">
                <div class="w-[272px]">
                    <Button
                        class="w-full cursor-pointer font-bold bg-[#ffffff4d] !justify-start text-white hover:bg-[#ffffff33]! h-[40px] rounded-lg"
                        @click="isAddingNewBoardList = !isAddingNewBoardList"
                    >
                        <Plus/>
                        Add another list
                    </Button>
                </div>
            </li>
        </ol>
    </div>
    <CardDialog :is-fetching="isFetching"
                :model-value="isDialogOpen"
                :selected-card="selectedCard"
                @update-open="onDialogClose"
    />
</template>

<style scoped>
</style>
