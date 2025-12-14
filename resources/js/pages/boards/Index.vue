<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import boardsRoutes from '@/routes/boards';
import type { BreadcrumbItem } from '@/types';
import type { Workspace } from '@/types/workspace/workspace';
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: boardsRoutes.index().url,
    },
];

const props = defineProps<{
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
}>();

onMounted(() => {
    router.visit(boardsRoutes.index().url, {
        only: ['ownedWorkspaces', 'sharedWorkspaces'],
        preserveScroll: true,
        preserveState: true,
    });
});
</script>

<template>
    <Head title="Boards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10">
                <div class="my-5 space-y-1">
                    <h3 class="font-semibold text-gray-300">YOUR WORKSPACES</h3>
                    <h4 class="text-sm text-gray-500">Workspaces you own</h4>
                </div>
                <div class="">
                    <template v-for="ownedWorkspace in props.ownedWorkspaces" :key="ownedWorkspace.id">
                        <div class="pb-10">
                            <div class="mb-4">
                                <h3 class="font-semibold">
                                    {{ ownedWorkspace.name }}
                                </h3>
                            </div>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                <template v-for="board in ownedWorkspace.boards" :key="board.id">
                                    <BoardCard :board="board" />
                                </template>
                                <BoardCardPopover :workspace-id="ownedWorkspace.id" />
                            </div>
                        </div>
                    </template>
                </div>
                <section v-if="props.sharedWorkspaces.length">
                    <div class="my-5 space-y-1">
                        <h3 class="font-semibold text-gray-300">SHARED WITH YOU</h3>
                        <h4 class="text-sm text-gray-500">Workspaces where you're a member</h4>
                    </div>
                    <div class="">
                        <template v-for="sharedWorkspace in props.sharedWorkspaces" :key="sharedWorkspace.id">
                            <div class="pb-10">
                                <div class="mb-4">
                                    <h3 class="font-semibold">
                                        {{ sharedWorkspace.name }}
                                    </h3>
                                </div>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                    <template v-for="board in sharedWorkspace.boards" :key="board.id">
                                        <BoardCard :board="board" />
                                    </template>
                                    <BoardCardPopover :workspace-id="sharedWorkspace.id" />
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
