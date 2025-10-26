<script lang="ts" setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem, User, Workspace } from "@/types";
import boardsRoutes from "@/routes/boards";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workspace Members',
        href: boardsRoutes.index().url
    },
];

defineProps<{
    workspace: Workspace,
    owner: User,
    members: User[],
}>();

</script>

<template>
    <Head :title="workspace.name + ' - Members'"/>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10">
                <div class="space-y-2">
                    <h1 class="text-lg font-medium">Workspace members</h1>
                    <p class="text-sm text-gray-300">Users who are members of a workspace have permission to view, join,
                        and create boards that belong to that workspace.</p>
                </div>
                <hr class="my-5">
                <div>
                    <div class="space-y-2 mb-5">
                        <h3 class="text-lg font-medium">Owner</h3>
                        <p><strong>{{owner.name}}</strong></p>
                    </div>
                </div>
                <hr class="my-5">
                <div>
                    <div class="space-y-2 mb-5">
                        <h3 class="text-lg font-medium">Members ({{members.length}})</h3>
                        <div class="min-h-10 py-4 border-y mb-0" v-for="( member, index ) in members" :key="member.id"
                             :class="{
                                'border-t-0': index === members.length - 1 && members.length > 1,
                             }"
                        >
                            <p>
                                <strong>{{member.name}}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
