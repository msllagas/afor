<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import ArchivedBoardsDialog from '@/components/workspace/ArchivedBoardsDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import { default as workspaceRoutes, default as workspaces } from '@/routes/workspaces';
import type { BreadcrumbItem, Workspace, WorkspaceMember } from '@/types';
import { Board } from '@/types';
import { Deferred, Head, router, useHttp, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { ArchiveXIcon, Link } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    boards: Board[];
    workspace: Workspace;
    members: WorkspaceMember[];
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
        title: 'Home',
        href: workspaceRoutes.members(props.workspace.id).url,
    },
];

const page = usePage();
const http = useHttp();

const user = page.props.auth.user;
const AVATAR_CAP = 5;
const copied = ref(false);
const showArchivedBoardsDialog = ref(false);

const boards = computed(() => props.boards); // created to push the newly created boards from the echo websocket event
const favoritedBoards = computed(() => props.boards?.filter((board) => board.is_favorited));

function copyInviteLink() {
    navigator.clipboard.writeText(props?.inviteLink);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2500);
}

function handleUnarchiveBoard(board: Board) {
    const index = boards.value.findIndex((b) => new Date(b.created_at) > new Date(board.created_at));

    if (index === -1) {
        boards.value.push(board);
    } else {
        boards.value.splice(index, 0, board);
    }
}

function handleStarBoard(board: Board) {
    // optimistically toggle locally
    board.is_favorited = !board.is_favorited;

    http.post(
        workspaceRoutes.boards.favorite({
            workspace: props.workspace,
            board: board,
        }).url,
        {
            onError: () => {
                toast.error('Failed to star board. Please try again.');
                // revert if fails
                board.is_favorited = !board.is_favorited;
            },
        },
    );
}

onMounted(() => {
    router.visit(workspaces.home(props.workspace.id).url, {
        only: ['workspace'],
        preserveScroll: true,
        preserveState: true,
    });
});

useEcho<BoardData>(`workspace.${props.workspace.id}`, 'BoardAddedToWorkspace', (e) => {
    boards.value.push(e.board);
});
</script>

<template>
    <Head :title="workspace.name + ' - Home'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pb-14 sm:px-10">
            <section>
                <header class="mt-10">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-5">
                        <div class="shrink-0">
                            <div
                                class="h-16 w-16 overflow-hidden rounded-2xl border border-border/50 shadow-md sm:h-20 sm:w-20"
                            >
                                <img :alt="workspace.name" :src="workspace.logo" class="h-full w-full object-cover" />
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
                                                        :src="member.avatar ?? ''"
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
                                class="cursor-pointer gap-2 shadow-sm"
                                size="sm"
                                variant="outline"
                                @click="copyInviteLink"
                            >
                                <Link class="h-3.5 w-3.5" />
                                Invite with link
                            </Button>
                        </div>
                    </div>
                </header>
            </section>

            <!-- Starred Boards -->
            <section v-if="favoritedBoards?.length">
                <div class="my-8 flex items-center gap-3">
                    <h3 class="text-base font-semibold tracking-tight">Starred Boards</h3>
                    <span class="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary tabular-nums">
                        {{ favoritedBoards?.length ?? 0 }}
                    </span>
                    <div class="h-px flex-1 bg-border/50" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Deferred data="boards">
                        <template #fallback>
                            <template v-for="i in 4" :key="i">
                                <Card class="w-full gap-2 overflow-hidden rounded-2xl pt-0 pb-2 shadow-lg">
                                    <CardContent class="h-24 p-0">
                                        <Skeleton class="h-full w-full rounded-none" />
                                    </CardContent>
                                    <CardFooter class="m-0.5 px-6">
                                        <Skeleton
                                            :class="
                                                cn(
                                                    'h-3.5 rounded-md',
                                                    i % 3 === 0 ? 'w-1/2' : i % 2 === 0 ? 'w-3/4' : 'w-2/3',
                                                )
                                            "
                                        />
                                    </CardFooter>
                                </Card>
                            </template>
                        </template>
                        <BoardCard
                            v-for="board in favoritedBoards"
                            :key="board.id"
                            :board="board"
                            @star-board="handleStarBoard"
                        />
                    </Deferred>
                </div>
            </section>

            <section>
                <div class="my-8 flex items-center gap-3">
                    <h3 class="text-base font-semibold tracking-tight">Boards</h3>
                    <span class="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary tabular-nums">
                        {{ boards?.length ?? 0 }}
                    </span>
                    <div class="h-px flex-1 bg-border/50" />
                    <Button
                        class="cursor-pointer"
                        size="sm"
                        variant="outline"
                        @click="showArchivedBoardsDialog = !showArchivedBoardsDialog"
                    >
                        <ArchiveXIcon class="h-3.5 w-3.5" />
                        View Archived Boards
                    </Button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Deferred data="boards">
                        <template #fallback>
                            <template v-for="i in 4" :key="i">
                                <Card class="w-full gap-2 overflow-hidden rounded-2xl pt-0 pb-2 shadow-lg">
                                    <CardContent class="h-24 p-0">
                                        <Skeleton class="h-full w-full rounded-none" />
                                    </CardContent>
                                    <CardFooter class="m-0.5 px-6">
                                        <Skeleton
                                            :class="
                                                cn(
                                                    'h-3.5 rounded-md',
                                                    i % 3 === 0 ? 'w-1/2' : i % 2 === 0 ? 'w-3/4' : 'w-2/3',
                                                )
                                            "
                                        />
                                    </CardFooter>
                                </Card>
                            </template>
                        </template>
                        <BoardCard
                            v-for="board in boards"
                            :key="board.id"
                            :board="board"
                            @star-board="handleStarBoard"
                        />
                        <BoardCardPopover :workspace-id="workspace.id" />
                    </Deferred>
                </div>
            </section>
        </div>
    </AppLayout>
    <ArchivedBoardsDialog
        v-model:open="showArchivedBoardsDialog"
        :workspace="workspace"
        @unarchive-board="handleUnarchiveBoard"
    />
</template>

<style scoped></style>
