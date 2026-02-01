<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { default as workspaceRoutes, default as workspaces } from '@/routes/workspaces';
import type { BreadcrumbItem, User, Workspace } from '@/types';
import { Board } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { Link } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    workspace: Workspace;
    members: User[];
    inviteLink: string;
}>();

type BoardData = {
    board: Board;
};

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

const boards = ref<Board[]>([]);
const AVATAR_CAP = 5;
const DUMMY_AVATAR_LINK = 'https://randomuser.me/api/portraits/lego/2.jpg'; // todo: for deletion once user avatars are implemented

function copyInviteLink() {
    navigator.clipboard.writeText(props?.inviteLink);
}

onMounted(() => {
    router.visit(workspaces.home(props.workspace.id).url, {
        only: ['workspace'],
        preserveScroll: true,
        preserveState: true,
    });
});

watch(
    () => props.workspace.boards,
    (newBoards) => {
        boards.value = [...newBoards];
    },
    { immediate: true },
);

useEcho<BoardData>(`workspace.${props.workspace.id}`, 'BoardAddedToWorkspace', (e) => {
    boards.value.push(e.board);
});
</script>

<template>
    <Head :title="workspace.name + ' - Boards'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="px-6 sm:px-10">
            <header class="mt-10 grid grid-cols-1 gap-6 pb-8 sm:grid-cols-[auto_1fr_25%] sm:items-center">
                <div class="shrink-0">
                    <div
                        class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border bg-muted sm:h-20 sm:w-20"
                    >
                        <img :alt="workspace.name" :src="DUMMY_AVATAR_LINK" class="h-full w-full object-cover" />
                        <!--                        <span v-else class="text-lg font-semibold text-muted-foreground">
                            {{ workspace.name.charAt(0) }}
                        </span>-->
                    </div>
                </div>
                <div class="space-y-2">
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ workspace.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ workspace.description }}
                    </p>
                </div>

                <div class="flex items-center justify-start gap-3 sm:justify-end">
                    <div class="flex -space-x-2">
                        <template v-for="member in members.slice(0, AVATAR_CAP)" :key="member.id">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Avatar
                                            class="h-8 w-8 border-2 border-background shadow-sm transition-transform hover:z-10 hover:scale-105"
                                        >
                                            <AvatarImage
                                                :alt="member.name"
                                                :src="DUMMY_AVATAR_LINK"
                                                class="object-cover"
                                            />
                                            <AvatarFallback class="bg-muted text-xs font-medium">
                                                {{ member.name.charAt(0) }}
                                            </AvatarFallback>
                                        </Avatar>
                                    </TooltipTrigger>
                                    <TooltipContent side="bottom">
                                        <p>{{ member.name }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </template>
                        <Avatar v-if="members.length > AVATAR_CAP">
                            <AvatarFallback class="text-xs font-medium text-muted-foreground">
                                +{{ members.length - AVATAR_CAP }}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                    <Button size="sm" @click="copyInviteLink">
                        <Link />
                        Invite with link
                    </Button>
                </div>
            </header>
            <hr class="my-8" />
        </section>
        <section class="px-6 sm:px-10">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-sm font-medium tracking-wide text-muted-foreground uppercase">Boards</h3>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <BoardCard v-for="board in boards" :key="board.id" :board="board" />
                <BoardCardPopover :workspace-id="workspace.id" />
            </div>
        </section>
    </AppLayout>
</template>

<style scoped></style>
