<script lang="ts" setup>
import BoardListController from '@/actions/App/Http/Controllers/BoardListController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import BoardList from '@/components/board/board-list/BoardList.vue';
import CardDialog from '@/components/board/board-list/card/CardDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import cardRoutes from '@/routes/board-lists/cards';
import boardRoutes from '@/routes/boards';
import boardListRoutes from '@/routes/boards/board-lists';
import type { Board, Card } from '@/types';
import { Form, Head, router } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps<{
    board: Board;
    selectedCard?: Card;
    colors: Array<string>;
}>();

const isDialogOpen = ref(false);
const isFetching = ref(false);
const isAddingNewBoardList = ref(false);
const headerTitle = ref(props.board.name);
const drag = ref(false);
const boardLists = ref([...props.board.board_lists]);
const input = useTemplateRef('add-new-list-input');
const boardName = ref(props.board.name);
const isEditingBoardName = ref(false);
const editedBoardName = ref(props.board.name);
const mirrorRef = ref<HTMLDivElement | null>(null);
const inputWidth = ref(0);
const inputRef = ref<HTMLInputElement | null>(null);

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
        const { oldIndex, newIndex } = event.moved;
        const start = Math.min(oldIndex, newIndex);
        const end = Math.max(oldIndex, newIndex);

        // vue-draggable already reordered the array
        // so just reassign orders for the affected slice only
        const changed = boardLists.value.slice(start, end + 1).map((list, i) => ({ id: list.id, order: start + i }));

        router.patch(
            boardListRoutes.reorder(boardId).url,
            {
                boardLists: changed,
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
function handleArchive(boardListId: string) {
    boardLists.value = boardLists.value.filter((list) => {
        return list.id !== boardListId;
    });

    router.patch(
        boardListRoutes.update({
            board: props.board.id,
            board_list: boardListId,
        }).url,
        {
            is_archived: true,
        },
    );
}

function startEditingBoardName() {
    editedBoardName.value = boardName.value;
    isEditingBoardName.value = true;
    nextTick(() => {
        if (mirrorRef.value) inputWidth.value = mirrorRef.value.offsetWidth;
        inputRef.value?.select();
        const input = inputRef.value;
        if (input) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
            input.scrollLeft = 999999;
        }
    });
}

function saveBoardName() {
    const sanitizedName = editedBoardName.value.trim();
    isEditingBoardName.value = false;

    if (!sanitizedName) {
        editedBoardName.value = boardName.value;
        return;
    }

    if (sanitizedName === boardName.value) return;

    boardName.value = sanitizedName;
    editedBoardName.value = sanitizedName;

    router.patch(
        boardRoutes.update({
            board: props.board.id,
        }).url,
        {
            name: sanitizedName,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                headerTitle.value = sanitizedName;
            },
        },
    );
}

watch(
    () => props.board,
    (newBoard) => (boardLists.value = [...newBoard.board_lists]),
);

watch(editedBoardName, () => {
    nextTick(() => {
        if (mirrorRef.value) {
            inputWidth.value = mirrorRef.value.offsetWidth;
        }
    });
});

onMounted(() => {
    if (props.selectedCard) {
        isDialogOpen.value = true;
    }
});
</script>

<template>
    <Head :title="headerTitle" />
    <div
        class="relative h-screen max-h-screen overflow-y-auto select-none"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
    >
        <nav
            class="fixed top-0 right-0 left-0 z-10 h-16 bg-[rgba(0,0,0,0.3)] shadow-[0_4px_30px_rgba(0,0,0,0.1)] backdrop-blur-sm"
        >
            <div class="flex h-full items-center gap-3 px-4">
                <div class="min-w-0 flex-1 overflow-hidden">
                    <h1
                        v-if="!isEditingBoardName"
                        class="inline-block max-w-full cursor-pointer truncate rounded-md px-2 py-1 text-lg font-semibold tracking-tight text-white transition-colors hover:bg-white/30 dark:hover:bg-white/30"
                        @click="startEditingBoardName"
                    >
                        {{ boardName }}
                    </h1>

                    <template v-else>
                        <span
                            ref="mirrorRef"
                            class="invisible absolute px-2 py-1 text-lg font-semibold tracking-tight whitespace-pre"
                            >{{ editedBoardName || ' ' }}</span
                        >
                        <input
                            ref="inputRef"
                            v-model="editedBoardName"
                            :style="{ width: inputWidth + 'px' }"
                            class="max-w-full rounded-md border border-blue-400 bg-white px-2 py-1 text-lg font-semibold tracking-tight outline-none dark:bg-gray-800"
                            maxlength="255"
                            @blur="saveBoardName"
                            @keydown.enter.prevent="saveBoardName"
                            @keydown.esc.prevent="saveBoardName"
                            @keydown.tab.prevent="saveBoardName"
                        />
                    </template>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <!-- Todo: Dummy button, delete when functionality has been implement -->

                    <AppearanceTabs />
                    <button
                        class="flex items-center gap-1.5 rounded-md bg-white/20 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-white/30"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M4 6h16M4 12h16M4 18h16"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </nav>
        <div class="mt-20">
            <ol
                v-if="board?.board_lists?.length > 0"
                class="absolute flex h-full max-h-[calc(100vh-128px)] gap-4 overflow-x-hidden overflow-y-hidden px-4"
            >
                <draggable
                    :component-data="{
                        tag: 'li',
                        type: 'transition-group',
                        name: !drag ? 'flip-list' : null,
                    }"
                    :list="boardLists"
                    class="flex gap-4"
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
                                :colors="colors"
                                :is-moving-board-list="drag"
                                @on-card-click="onCardClick"
                                @on-list-archive="handleArchive"
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
