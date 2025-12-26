<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { default as workspaceRoutes, default as workspaces } from '@/routes/workspaces';
import type { BreadcrumbItem, Workspace } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{
    workspace: Workspace;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workspace',
        href: workspaceRoutes.home(props.workspace.id).url,
    },
    {
        title: 'Boards',
        href: workspaceRoutes.members(props.workspace.id).url,
    },
];

onMounted(() => {
    router.visit(workspaces.home(props.workspace.id).url, {
        only: ['workspace'],
        preserveScroll: true,
        preserveState: true,
    });
});
</script>

<template>
    <Head :title="workspace.name + ' - Boards'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10">
                <div class="space-y-2 pb-10">
                    <h1 class="text-lg font-medium">{{ workspace.name }}</h1>
                    <p class="text-sm">{{ workspace.description }}</p>
                </div>
                <hr class="my-5" />
                <div>
                    <div class="mb-5">
                        <h3 class="font-medium">All boards in this Workspace</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <template v-for="board in workspace.boards" :key="board.id">
                            <BoardCard :board="board" />
                        </template>
                        <BoardCardPopover :workspace-id="workspace.id" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
