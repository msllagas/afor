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
import { Head, router, usePage } from '@inertiajs/vue3';
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

const page = usePage();
const user = page.props.auth.user;
const boards = ref<Board[]>([]);
const AVATAR_CAP = 5;
const DUMMY_AVATAR_LINK = 'https://randomuser.me/api/portraits/lego/2.jpg'; // todo: for deletion once user avatars are implemented
const copied = ref(false);

function copyInviteLink() {
    navigator.clipboard.writeText(props?.inviteLink);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2500);
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
        <div class="px-6 sm:px-10">
            <section>
                <header class="mt-10">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-5">
                        <div class="shrink-0">
                            <div
                                class="h-16 w-16 overflow-hidden rounded-2xl border border-border/50 shadow-md sm:h-20 sm:w-20"
                            >
                                <img
                                    :alt="workspace.name"
                                    :src="DUMMY_AVATAR_LINK"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                        <div class="min-w-0 space-y-1">
                            <h1 class="truncate text-xl font-semibold tracking-tight">{{ workspace.name }}</h1>
                            <p class="line-clamp-2 text-sm text-muted-foreground">{{ workspace.description }}</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex -space-x-2">
                                <template v-for="member in members.slice(0, AVATAR_CAP)" :key="member.id">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Avatar
                                                    class="h-8 w-8 border-2 border-background shadow-sm transition-all duration-200 hover:z-10 hover:-translate-y-0.5 hover:scale-110 hover:shadow-md"
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
                                                <p>{{ user.id === member.id ? 'You' : member.name }}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </template>
                                <Avatar
                                    v-if="members.length > AVATAR_CAP"
                                    class="h-8 w-8 border-2 border-background shadow-sm"
                                >
                                    <AvatarFallback class="bg-muted text-xs font-medium text-muted-foreground">
                                        +{{ members.length - AVATAR_CAP }}
                                    </AvatarFallback>
                                </Avatar>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ members.length }} member{{ members.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div class="relative flex flex-col items-end gap-1">
                            <Transition
                                enter-active-class="transition-all duration-300"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-200"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <p
                                    v-if="copied"
                                    class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs font-medium whitespace-nowrap text-emerald-500"
                                >
                                    ✓ Link copied to clipboard!
                                </p>
                            </Transition>

                            <Button
                                size="sm"
                                variant="outline"
                                class="cursor-pointer gap-2 shadow-sm"
                                @click="copyInviteLink"
                            >
                                <Link class="h-3.5 w-3.5" />
                                Invite with link
                            </Button>
                        </div>
                    </div>
                </header>
            </section>
            <section>
                <div class="my-8 flex items-center gap-3">
                    <h3 class="text-base font-semibold tracking-tight">Boards</h3>
                    <span class="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary tabular-nums">
                        {{ boards.length }}
                    </span>
                    <div class="h-px flex-1 bg-border/50" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <BoardCard v-for="board in boards" :key="board.id" :board="board" />
                    <BoardCardPopover :workspace-id="workspace.id" />
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped></style>
