<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import BoardRow from '@/components/board/BoardRow.vue';
import HighlightMatch from '@/components/HighlightMatch.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import WorkspaceAvatar from '@/components/workspace/WorkspaceAvatar.vue';
import { home } from '@/routes/workspaces';
import type { Board, Workspace } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const LIST_VIEW_LIMIT = 6;

const props = defineProps<{
    workspace: Workspace;
    /** The boards that pass the current search and scope. */
    boards: Board[];
    view: 'grid' | 'list';
    columns: number;
    query: string;
    isCurrent: boolean;
    /** Only the workspace owner can add boards; members see the ones they were added to. */
    canAddBoard: boolean;
    isOwned: boolean;
    isShowingAll: boolean;
}>();

const emit = defineEmits<{
    toggleShowAll: [];
    starBoard: [board: Board, isStarred: boolean];
}>();

const isOpen = defineModel<boolean>('open', { required: true });

const headingId = computed(() => `workspace-${props.workspace.id}`);
const listId = computed(() => `workspace-${props.workspace.id}-boards`);

// Keep long workspaces to a couple of rows; the "New board" tile fills the last slot.
const limit = computed(() => {
    if (props.view === 'list') {
        return LIST_VIEW_LIMIT;
    }

    const slots = props.columns === 1 ? 3 : props.columns * 2;

    return props.canAddBoard ? slots - 1 : slots;
});

const visibleBoards = computed(() =>
    props.isShowingAll ? props.boards : props.boards.slice(0, Math.max(limit.value, 1)),
);

const hiddenCount = computed(() => props.boards.length - Math.min(props.boards.length, Math.max(limit.value, 1)));
</script>

<template>
    <section
        :aria-labelledby="headingId"
        :data-workspace-section="workspace.id"
        class="scroll-mt-[calc(var(--boards-toolbar-height,4rem)+1rem)]"
    >
        <Collapsible v-model:open="isOpen">
            <div class="flex items-center gap-2">
                <h3 :id="headingId" class="min-w-0 flex-1">
                    <CollapsibleTrigger
                        class="group/trigger -ml-2 flex w-full cursor-pointer items-center gap-3 rounded-xl px-2 py-1.5 text-left outline-none hover:bg-muted/70 focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        data-section-trigger
                    >
                        <ChevronRight
                            aria-hidden="true"
                            class="size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]/trigger:rotate-90 motion-reduce:transition-none"
                        />
                        <WorkspaceAvatar :workspace="workspace" class="size-9 rounded-lg" />
                        <span class="flex min-w-0 flex-1 items-center gap-2">
                            <HighlightMatch :query="query" :text="workspace.name" class="truncate font-semibold" />
                            <span
                                v-if="isCurrent"
                                class="shrink-0 rounded-full bg-blush px-2 py-0.5 text-[11px] font-medium text-blush-foreground"
                            >
                                Current
                            </span>
                        </span>
                    </CollapsibleTrigger>
                </h3>
                <Link
                    :href="home(workspace.id)"
                    class="shrink-0 rounded-md px-2 py-1.5 text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    Open<span class="sr-only"> {{ workspace.name }} workspace</span>
                </Link>
            </div>

            <CollapsibleContent class="pt-3 pb-1">
                <p
                    v-if="!isOwned && !workspace.boards.length"
                    class="rounded-xl border border-dashed px-4 py-3 text-sm text-muted-foreground"
                >
                    You haven’t been added to any boards here yet. Ask the workspace owner to add you.
                </p>
                <ul
                    v-if="view === 'grid'"
                    :id="listId"
                    class="grid list-none grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] gap-4"
                >
                    <li v-for="board in visibleBoards" :key="board.id">
                        <BoardCard
                            :board="board"
                            :query="query"
                            class="h-full"
                            @star-board="(...args) => emit('starBoard', ...args)"
                        />
                    </li>
                    <li v-if="canAddBoard">
                        <BoardCardPopover :workspace-id="workspace.id" />
                    </li>
                </ul>

                <ul v-else :id="listId" class="grid list-none gap-2">
                    <li v-for="board in visibleBoards" :key="board.id">
                        <BoardRow :board="board" :query="query" @star-board="(...args) => emit('starBoard', ...args)" />
                    </li>
                    <li v-if="canAddBoard">
                        <BoardCardPopover :workspace-id="workspace.id" variant="row" />
                    </li>
                </ul>

                <button
                    v-if="hiddenCount > 0"
                    :aria-controls="listId"
                    :aria-expanded="isShowingAll"
                    class="mt-3 cursor-pointer rounded-md px-2 py-1.5 text-sm font-medium text-muted-foreground outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    type="button"
                    @click="emit('toggleShowAll')"
                >
                    {{ isShowingAll ? 'Show fewer boards' : 'Show all boards' }}
                </button>
            </CollapsibleContent>
        </Collapsible>
    </section>
</template>
