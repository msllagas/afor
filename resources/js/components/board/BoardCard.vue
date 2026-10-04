<script lang="ts" setup>
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { show } from '@/routes/boards';
import { Board } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const PREVIEW_LIST_CAP = 5;
const PREVIEW_CARD_CAP = 4;
const PLACEHOLDER_LISTS = [
    { id: 'placeholder-1', color: 'neutral', cards_count: 2 },
    { id: 'placeholder-2', color: 'neutral', cards_count: 1 },
    { id: 'placeholder-3', color: 'neutral', cards_count: 0 },
];

const props = defineProps<{
    board: Board;
}>();

defineEmits<{
    starBoard: [board: Board, isStarred: boolean];
}>();

const isStarred = ref(props.board?.is_favorited);

watch(
    () => props.board?.is_favorited,
    (value) => {
        isStarred.value = value;
    },
);

const hasListPreview = computed(() => Array.isArray(props.board.board_lists));

const previewLists = computed(() =>
    hasListPreview.value ? props.board.board_lists.slice(0, PREVIEW_LIST_CAP) : PLACEHOLDER_LISTS,
);

const cardCount = computed(() =>
    (props.board.board_lists ?? []).reduce((total, list) => total + (list.cards_count ?? 0), 0),
);

const summary = computed(() => {
    const listCount = props.board.board_lists?.length ?? 0;

    if (listCount === 0) {
        return 'No lists yet';
    }

    return `${listCount} ${listCount === 1 ? 'list' : 'lists'}, ${cardCount.value} ${cardCount.value === 1 ? 'card' : 'cards'}`;
});
</script>

<template>
    <div class="group relative">
        <Link
            :href="show(board.id)"
            class="flex h-full flex-col overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-xs transition-[border-color,box-shadow] duration-200 outline-none group-hover:border-primary/40 group-hover:shadow-md group-hover:shadow-primary/5 focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50"
        >
            <div aria-hidden="true" class="flex h-24 items-start gap-1.5 overflow-hidden bg-blush/60 px-3 pt-3">
                <div
                    v-for="list in previewLists"
                    :key="list.id"
                    :class="`list-${list.color ?? 'neutral'}`"
                    class="flex w-11 shrink-0 flex-col gap-1 rounded-md bg-(--list-bg) p-1"
                >
                    <span class="h-1 w-4 rounded-full bg-(--list-fg-muted)" />
                    <span
                        v-for="card in Math.min(list.cards_count ?? 0, PREVIEW_CARD_CAP)"
                        :key="card"
                        class="h-2.5 rounded-xs bg-card/90"
                    />
                </div>
                <p v-if="hasListPreview && !previewLists.length" class="self-center text-xs text-blush-foreground">
                    Empty board
                </p>
            </div>
            <div class="flex flex-1 flex-col gap-0.5 px-4 py-3">
                <span class="truncate text-sm font-semibold">{{ board.name }}</span>
                <span v-if="hasListPreview" class="text-xs text-muted-foreground tabular-nums">{{ summary }}</span>
            </div>
        </Link>
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        type="button"
                        :aria-label="isStarred ? `Unstar ${board.name}` : `Star ${board.name}`"
                        :aria-pressed="isStarred"
                        :class="
                            cn(
                                'group/star absolute top-2 right-2 z-10 flex size-8 cursor-pointer items-center justify-center rounded-full bg-card/90 text-muted-foreground shadow-xs backdrop-blur transition-[opacity,color] duration-200 outline-none hover:text-primary focus-visible:opacity-100 focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                isStarred
                                    ? 'text-primary opacity-100'
                                    : 'opacity-0 group-hover:opacity-100 pointer-coarse:opacity-100',
                            )
                        "
                        @click.prevent="$emit('starBoard', board, (isStarred = !isStarred))"
                    >
                        <Star
                            :class="
                                cn(
                                    'size-4 transition-transform duration-200',
                                    isStarred ? 'fill-current' : 'fill-transparent group-hover/star:scale-110',
                                )
                            "
                        />
                    </button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>{{ isStarred ? 'Unstar' : 'Star' }} this board</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
