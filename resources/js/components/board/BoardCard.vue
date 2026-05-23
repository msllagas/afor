<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import boardsRoutes from '@/routes/boards';
import { Board } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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
</script>

<template>
    <div class="group relative">
        <Link :href="boardsRoutes.show(board.id).url">
            <Card :class="cn('w-full gap-2 overflow-hidden rounded-2xl pt-0 pb-2 shadow-lg', $attrs.class ?? '')">
                <CardContent
                    class="group relative flex h-24 items-end overflow-hidden bg-linear-to-r from-primary via-fuchsia-500 to-rose-400 p-4"
                >
                    <div
                        class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/20"
                    ></div>
                </CardContent>
                <CardFooter class="m-0.5 px-6">
                    <span class="tracking-light relative block truncate text-sm font-medium">
                        {{ board.name }}
                    </span>
                </CardFooter>
            </Card>
        </Link>
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        size="icon"
                        :class="
                            cn(
                                'group/star absolute top-2 right-2 z-10 cursor-pointer transition-all duration-200',
                                isStarred ? 'opacity-100' : 'opacity-0 group-hover:opacity-100',
                            )
                        "
                        @click.prevent="$emit('starBoard', board, (isStarred = !isStarred))"
                    >
                        <Star
                            :class="
                                cn(
                                    'transition-transform duration-200',
                                    isStarred
                                        ? 'scale-125 fill-current'
                                        : 'fill-transparent group-hover/star:scale-125',
                                )
                            "
                        />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>Click to {{ isStarred ? 'unstar' : 'star' }} this board.</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>

<style scoped></style>
