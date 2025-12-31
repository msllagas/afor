<script lang="ts" setup>
import BoardListController from '@/actions/App/Http/Controllers/BoardListController';
import BoardList from '@/components/board/board-list/BoardList.vue';
import CardDialog from '@/components/board/board-list/card/CardDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { reorderDraggable } from '@/lib/utils';
import cardRoutes from '@/routes/board-lists/cards';
import boardRoutes from '@/routes/boards';
import boardListRoutes from '@/routes/boards/board-lists';
import type { Board, Card } from '@/types';
import { Form, Head, router } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, provide, ref, useTemplateRef } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps<{
    board: Board;
    selectedCard?: Card;
}>();

const isDialogOpen = ref(false);
const isFetching = ref(false);
const isAddingNewBoardList = ref(false);
const headerTitle = ref(props.board.name);
const drag = ref(false);
const input = useTemplateRef('add-new-list-input');

const dragOptions = computed(() => ({
    animation: 200,
    group: {
        name: 'boardList',
        pull: true,
        put: false,
    },
    ghostClass: 'ghost',
    dragClass: 'drag',
    forceFallback: true,
    disabled: false,
    scrollSensitivity: 100,
    scrollSpeed: 20,
}));

function onCardClick(boardListId: string, card: Card) {
    isFetching.value = true;
    isDialogOpen.value = true;

    const url = cardRoutes.show({
        board_list: boardListId,
        card: card.id,
    }).url;
    headerTitle.value = card.name;

    window.history.pushState({}, '', url);

    router.visit(url, {
        only: ['selectedCard'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            isFetching.value = false;
        },
    });
}

function onDialogClose(value: boolean) {
    if (!value) {
        headerTitle.value = props.board.name;
        isDialogOpen.value = false;

        window.history.pushState({}, '', boardRoutes.show(props.board.id).url);
        router.visit(boardRoutes.show(props.board.id).url, {
            only: ['board', 'selectedCard'],
            preserveScroll: true,
            preserveState: true,
        });
    }
}

function onChange(boardId: string, event: any) {
    if (event.moved) {
        const changedBoardLists = reorderDraggable(props.board.board_lists, event.moved.oldIndex, event.moved.newIndex);

        router.patch(
            boardListRoutes.reorder(boardId).url,
            {
                boardLists: changedBoardLists.map((b) => ({
                    id: b.id,
                    order: b.order,
                })),
            },
            {
                replace: true,
            },
        );
    }
}

async function onAddNewBoardList() {
    isAddingNewBoardList.value = true;
    await nextTick();
    input.value?.focus();
}

provide('boardId', props.board.id);

onMounted(() => {
    if (props.selectedCard) {
        isDialogOpen.value = true;
    }
});
</script>

<template>
    <Head :title="headerTitle" />
    <div
        class="relative h-screen max-h-screen overflow-y-auto bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 select-none"
    >
        <div class="shadow-[0 4px 30px rgba(0, 0, 0, 0.1)] bg-[rgba(0,0,0,0.3)] p-4 backdrop-blur-xs">
            <div>
                <h1 class="text-lg font-medium">{{ board.name }}</h1>
            </div>
        </div>
        <div class="mt-2">
            <ol
                v-if="board?.board_lists?.length > 0"
                class="absolute flex h-full max-h-[calc(100vh-128px)] gap-2 overflow-x-hidden overflow-y-hidden px-2"
            >
                <draggable
                    :component-data="{
                        tag: 'li',
                        type: 'transition-group',
                        name: !drag ? 'flip-list' : null,
                    }"
                    :list="board.board_lists"
                    class="flex gap-2"
                    handle=".handle"
                    item-key="id"
                    v-bind="dragOptions"
                    @change="onChange(board.id, $event)"
                    @end="drag = false"
                    @start="drag = true"
                >
                    <template #item="{ element }">
                        <li>
                            <BoardList
                                :key="element.id"
                                :board-list="element"
                                :is-moving-board-list="drag"
                                @on-card-click="onCardClick"
                            />
                        </li>
                    </template>
                </draggable>
                <li v-if="isAddingNewBoardList" class="block h-full shrink-0 self-start rounded-lg whitespace-nowrap">
                    <div class="w-[272px] rounded-lg bg-black p-2">
                        <Form
                            v-slot="{ processing }"
                            class="space-y-6"
                            reset-on-success
                            v-bind="BoardListController.store.form(board.id)"
                        >
                            <Input
                                id="name"
                                ref="add-new-list-input"
                                class="mb-2 w-full rounded-lg p-2 shadow"
                                name="name"
                            />
                            <div class="flex items-center gap-2">
                                <Button :disabled="processing" class="cursor-pointer" data-test="update-profile-button">
                                    Add list
                                </Button>
                                <Button
                                    class="cursor-pointer"
                                    size="sm"
                                    variant="ghost"
                                    @click="isAddingNewBoardList = false"
                                >
                                    <X />
                                </Button>
                            </div>
                        </Form>
                    </div>
                </li>
                <li v-else class="block h-full shrink-0 self-start whitespace-nowrap">
                    <div class="w-[272px]">
                        <Button
                            class="h-[40px] w-full cursor-pointer !justify-start rounded-lg bg-[#ffffff4d] font-bold text-white hover:bg-[#ffffff33]!"
                            @click="onAddNewBoardList"
                        >
                            <Plus />
                            Add another list
                        </Button>
                    </div>
                </li>
            </ol>
        </div>
    </div>
    <CardDialog
        :is-fetching="isFetching"
        :model-value="isDialogOpen"
        :selected-card="selectedCard"
        @update-open="onDialogClose"
    />
</template>

<style scoped></style>
