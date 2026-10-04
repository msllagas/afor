<script lang="ts" setup>
import HighlightMatch from '@/components/HighlightMatch.vue';
import { cn } from '@/lib/utils';
import { show } from '@/routes/boards';
import type { Board } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed } from 'vue';

const SWATCH_CAP = 6;

const props = defineProps<{
    board: Board;
    query?: string;
}>();

const emit = defineEmits<{
    starBoard: [board: Board, isStarred: boolean];
}>();

const lists = computed(() => props.board.board_lists ?? []);

const summary = computed(() => {
    const listCount = lists.value.length;

    if (listCount === 0) {
        return 'No lists yet';
    }

    const cardCount = lists.value.reduce((total, list) => total + (list.cards_count ?? 0), 0);

    return `${listCount} ${listCount === 1 ? 'list' : 'lists'}, ${cardCount} ${cardCount === 1 ? 'card' : 'cards'}`;
});
</script>

<template>
    <div class="group relative">
        <Link
            :href="show(board.id)"
            class="flex min-h-11 items-center gap-3 rounded-xl border bg-card py-2 pr-12 pl-3 text-card-foreground transition-[border-color,box-shadow] duration-200 outline-none group-hover:border-primary/40 group-hover:shadow-sm focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50"
        >
            <span aria-hidden="true" class="flex w-12 shrink-0 gap-0.5">
                <span
                    v-for="list in lists.slice(0, SWATCH_CAP)"
                    :key="list.id"
                    :class="`list-${list.color ?? 'neutral'}`"
                    class="h-5 w-1.5 rounded-full bg-(--list-bg) ring-1 ring-black/5 dark:ring-white/10"
                />
                <span v-if="!lists.length" class="h-5 w-full rounded-md border border-dashed" />
            </span>
            <span class="min-w-0 flex-1 sm:flex sm:items-baseline sm:gap-3">
                <HighlightMatch
                    :query="query"
                    :text="board.name"
                    class="block truncate text-sm font-medium sm:flex-1"
                />
                <span class="block text-xs text-muted-foreground tabular-nums sm:shrink-0">{{ summary }}</span>
            </span>
        </Link>
        <button
            :aria-label="board.is_favorited ? `Unstar ${board.name}` : `Star ${board.name}`"
            :aria-pressed="!!board.is_favorited"
            :class="
                cn(
                    'absolute top-1/2 right-1.5 flex size-8 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-[opacity,color] duration-200 outline-none hover:text-primary focus-visible:opacity-100 focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    board.is_favorited
                        ? 'text-primary opacity-100'
                        : 'opacity-0 group-hover:opacity-100 pointer-coarse:opacity-100',
                )
            "
            :title="board.is_favorited ? 'Unstar this board' : 'Star this board'"
            type="button"
            @click="emit('starBoard', board, !board.is_favorited)"
        >
            <Star :class="board.is_favorited ? 'fill-current' : 'fill-transparent'" aria-hidden="true" class="size-4" />
        </button>
    </div>
</template>
