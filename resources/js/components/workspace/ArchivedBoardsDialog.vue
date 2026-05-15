<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import boardRoutes from '@/routes/boards';
import workspaceRoutes from '@/routes/workspaces';
import { Board, Workspace } from '@/types';
import { useHttp } from '@inertiajs/vue3';
import { Archive } from 'lucide-vue-next';
import { computed, ref, useAttrs, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    workspace: Workspace;
}>();

const emit = defineEmits<{
    // no delete emit because this component needs it more than the parent
    unarchiveBoard: [board: Board]; // needed by the parent to add the board to the workspace for optimistic UI
}>();

const attrs = useAttrs();
const http = useHttp({});

const isFetchingArchivedBoards = ref(false);
const archivedBoards = ref<Board[] | null>(null);

const open = computed(() => attrs.open);

function unarchiveBoard(board: Board) {
    // For Optimistic UI
    const previous = archivedBoards.value;
    archivedBoards.value = archivedBoards.value?.filter((b: Board) => b.id !== board.id) ?? null;

    http.patch(
        boardRoutes.unarchive({
            board: board,
        }).url,
        {
            onSuccess: (data) => {
                emit('unarchiveBoard', data as Board);
            },
            onError: () => {
                toast.error('Failed to unarchive board. Please try again.');
                archivedBoards.value = previous;
            },
        },
    );
}

function deleteBoard(board: Board) {
    // For Optimistic UI
    const previous = archivedBoards.value;
    archivedBoards.value = archivedBoards.value?.filter((b: Board) => b.id !== board.id) ?? null;

    http.delete(
        boardRoutes.destroy({
            board: board,
        }).url,
        {
            onError: () => {
                toast.error('Failed to delete board. Please try again.');
                archivedBoards.value = previous;
            },
        },
    );
}

watch(open, (newValue) => {
    if (newValue) {
        isFetchingArchivedBoards.value = true;

        http.get(workspaceRoutes.boards.archived(props.workspace.id).url, {
            onSuccess: (data) => {
                isFetchingArchivedBoards.value = false;
                archivedBoards.value = data as Board[];
            },
            onError: () => {
                isFetchingArchivedBoards.value = false;
            },
        });
    } else {
        isFetchingArchivedBoards.value = false;
        http.cancel();
    }
});
</script>

<template>
    <Dialog>
        <DialogContent class="grid max-h-[90dvh] grid-rows-[auto_1fr] overflow-hidden">
            <DialogHeader>
                <DialogTitle>
                    <span class="flex items-center gap-2">
                        <Archive class="h-4 w-4" />
                        Archived Boards
                    </span>
                </DialogTitle>
                <DialogDescription> All your archived boards </DialogDescription>
            </DialogHeader>
            <div v-if="isFetchingArchivedBoards" class="flex flex-col gap-2">
                <div v-for="i in 3" :key="i" class="flex items-center justify-between rounded-md border p-3">
                    <div class="flex flex-col gap-2">
                        <Skeleton class="h-4 w-40" />
                        <Skeleton class="h-3 w-24" />
                    </div>
                    <div class="flex gap-2">
                        <Skeleton class="h-8 w-24" />
                        <Skeleton class="h-8 w-16" />
                    </div>
                </div>
            </div>
            <div v-else-if="archivedBoards && archivedBoards.length > 0" class="overflow-hidden">
                <ScrollArea class="h-full w-full rounded-md border p-4">
                    <TransitionGroup name="board-list" tag="div" class="flex flex-col gap-2">
                        <div
                            v-for="board in archivedBoards"
                            :key="board.id"
                            class="flex items-center justify-between rounded-md border p-3"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ board.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ workspace.name }}</p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    class="cursor-pointer"
                                    variant="outline"
                                    size="sm"
                                    @click="unarchiveBoard(board)"
                                >
                                    Unarchive
                                </Button>
                                <Button
                                    class="cursor-pointer"
                                    variant="destructive"
                                    size="sm"
                                    @click="deleteBoard(board)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>
                    </TransitionGroup>
                </ScrollArea>
            </div>
            <div v-else class="py-4">
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Archive />
                        </EmptyMedia>
                        <EmptyTitle>No Archived Boards</EmptyTitle>
                        <EmptyDescription> Boards you archive will appear here. </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.board-list-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.board-list-move {
    transition: transform 0.3s ease;
}

.board-list-leave-to {
    opacity: 0;
    max-height: 0;
    padding: 0;
}

.board-list-leave-from {
    max-height: 100px;
    padding: 12px;
}
</style>
