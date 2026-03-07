<script lang="ts" setup>
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import boardsRoutes from '@/routes/boards';
import { Board } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    board: Board;
}>();
</script>

<template>
    <Link :href="boardsRoutes.show(board.id).url">
        <Card :class="cn('w-full gap-2 overflow-hidden rounded-2xl pt-0 pb-2 shadow-lg', $attrs.class ?? '')">
            <CardContent
                class="group relative flex h-24 items-end overflow-hidden bg-gradient-to-r from-primary via-fuchsia-500 to-rose-400 p-4"
            >
                <!-- overlay -->
                <div class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/20"></div>
            </CardContent>
            <CardFooter class="m-0.5 px-6">
                <span class="tracking-light relative text-sm font-medium">
                    {{ board.name }}
                </span>
            </CardFooter>
        </Card>
    </Link>
</template>

<style scoped></style>
