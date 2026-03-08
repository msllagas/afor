<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import AppLayout from '@/layouts/AppLayout.vue';
import boardsRoutes from '@/routes/boards';
import type { BreadcrumbItem } from '@/types';
import type { Workspace } from '@/types/workspace/workspace';
import { Head, router } from '@inertiajs/vue3';
import { Crown, Users } from 'lucide-vue-next';
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

const DUMMY_AVATAR_LINK = 'https://randomuser.me/api/portraits/lego/2.jpg'; // todo: for deletion once user avatars are implemented

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
        <div class="px-6 pb-16 sm:px-10">
            <div class="mt-10 space-y-14">
                <section>
                    <div class="mb-7 flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <Crown class="h-4 w-4 text-primary" />
                            <h2 class="text-base font-semibold tracking-tight">Your workspaces</h2>
                        </div>
                        <div class="h-px flex-1 bg-border/50" />
                    </div>

                    <div class="space-y-10">
                        <div v-for="ownedWorkspace in props.ownedWorkspaces" :key="ownedWorkspace.id">
                            <div class="mb-4 flex w-fit items-center gap-3">
                                <Avatar class="h-8 w-8 rounded-lg">
                                    <AvatarImage
                                        :alt="ownedWorkspace.name"
                                        :src="DUMMY_AVATAR_LINK"
                                        class="object-cover"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-primary text-xs font-bold text-primary-foreground"
                                    >
                                        {{ ownedWorkspace.name.charAt(0).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="text-base font-semibold tracking-tight">{{ ownedWorkspace.name }}</span>
                                <span
                                    class="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary tabular-nums"
                                >
                                    {{ ownedWorkspace.boards.length }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <BoardCard v-for="board in ownedWorkspace.boards" :key="board.id" :board="board" />
                                <BoardCardPopover :workspace-id="ownedWorkspace.id" />
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="props.sharedWorkspaces.length">
                    <div class="mb-7 flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <Users class="h-4 w-4 text-muted-foreground" />
                            <h2 class="text-base font-semibold tracking-tight text-muted-foreground">
                                Shared with you
                            </h2>
                        </div>
                        <div class="h-px flex-1 bg-border/50" />
                    </div>

                    <div class="space-y-10">
                        <div v-for="sharedWorkspace in props.sharedWorkspaces" :key="sharedWorkspace.id">
                            <div class="mb-4 flex items-center gap-3">
                                <Avatar class="h-8 w-8 rounded-lg opacity-75">
                                    <AvatarImage
                                        :alt="sharedWorkspace.name"
                                        :src="DUMMY_AVATAR_LINK"
                                        class="object-cover"
                                    />
                                    <AvatarFallback class="rounded-lg bg-muted text-xs font-bold text-muted-foreground">
                                        {{ sharedWorkspace.name.charAt(0).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="text-base font-medium tracking-tight text-muted-foreground">{{
                                    sharedWorkspace.name
                                }}</span>
                                <span
                                    class="rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                                >
                                    {{ sharedWorkspace.boards.length }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <BoardCard v-for="board in sharedWorkspace.boards" :key="board.id" :board="board" />
                                <BoardCardPopover :workspace-id="sharedWorkspace.id" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
