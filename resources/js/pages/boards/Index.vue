<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/boards';
import { home } from '@/routes/workspaces';
import { favorite } from '@/routes/workspaces/boards';
import type { Board, BreadcrumbItem, Workspace } from '@/types';
import { Head, Link, useHttp } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: index().url,
    },
];

const props = defineProps<{
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
}>();

const { getInitials } = useInitials();
const http = useHttp();

const workspaceGroups = computed(() =>
    [
        { id: 'owned', title: 'Your workspaces', workspaces: props.ownedWorkspaces },
        { id: 'shared', title: 'Shared with you', workspaces: props.sharedWorkspaces },
    ].filter((group) => group.workspaces.length),
);

const allBoards = computed(() =>
    [...props.ownedWorkspaces, ...props.sharedWorkspaces].flatMap((workspace) => workspace.boards),
);

const starredBoards = computed(() => allBoards.value.filter((board) => board.is_favorited));

const summary = computed(() => {
    const boardCount = allBoards.value.length;
    const workspaceCount = props.ownedWorkspaces.length + props.sharedWorkspaces.length;

    return `${boardCount} ${boardCount === 1 ? 'board' : 'boards'} across ${workspaceCount} ${workspaceCount === 1 ? 'workspace' : 'workspaces'}`;
});

function pluralizeBoards(count: number) {
    return `${count} ${count === 1 ? 'board' : 'boards'}`;
}

function handleStarBoard(board: Board, isStarred: boolean) {
    board.is_favorited = isStarred;

    http.post(favorite({ workspace: board.workspace_id, board: board.id }).url, {
        onError: () => {
            toast.error('Could not update the star. Try again.');
            board.is_favorited = !isStarred;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Boards">
            <link
                head-key="font-fraunces"
                href="https://fonts.bunny.net/css?family=fraunces:500,600"
                rel="stylesheet"
            />
        </Head>

        <div class="mx-auto w-full max-w-7xl px-4 pt-8 pb-16 sm:px-6 sm:pt-10 lg:px-10">
            <header>
                <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Boards</h1>
                <p class="mt-1.5 text-sm text-muted-foreground">{{ summary }}</p>
            </header>

            <section v-if="starredBoards.length" aria-labelledby="starred-heading" class="mt-10">
                <h2 id="starred-heading" class="mb-4 flex items-center gap-2 text-base font-semibold">
                    <Star class="size-4 fill-primary text-primary" aria-hidden="true" />
                    Starred
                </h2>
                <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                    <BoardCard
                        v-for="board in starredBoards"
                        :key="board.id"
                        :board="board"
                        @star-board="handleStarBoard"
                    />
                </div>
            </section>

            <section
                v-for="group in workspaceGroups"
                :key="group.id"
                :aria-labelledby="`${group.id}-heading`"
                class="mt-12"
            >
                <h2
                    :id="`${group.id}-heading`"
                    class="border-b pb-3 font-display text-xl font-semibold tracking-tight sm:text-2xl"
                >
                    {{ group.title }}
                </h2>

                <div class="mt-6 space-y-10">
                    <article
                        v-for="workspace in group.workspaces"
                        :key="workspace.id"
                        :aria-labelledby="`workspace-${workspace.id}`"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <Avatar class="size-9 rounded-lg">
                                <AvatarImage :alt="workspace.name" :src="workspace.logo ?? ''" class="object-cover" />
                                <AvatarFallback class="rounded-lg bg-blush text-xs font-semibold text-blush-foreground">
                                    {{ getInitials(workspace.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0 flex-1">
                                <h3 :id="`workspace-${workspace.id}`" class="truncate font-semibold">
                                    {{ workspace.name }}
                                </h3>
                                <p class="text-xs text-muted-foreground tabular-nums">
                                    {{ pluralizeBoards(workspace.boards.length) }}
                                </p>
                            </div>
                            <Link
                                :href="home(workspace.id)"
                                class="shrink-0 rounded-md px-2 py-1 text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                Open<span class="sr-only"> {{ workspace.name }}</span> workspace
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                            <BoardCard
                                v-for="board in workspace.boards"
                                :key="board.id"
                                :board="board"
                                @star-board="handleStarBoard"
                            />
                            <BoardCardPopover :workspace-id="workspace.id" />
                        </div>
                    </article>
                </div>
            </section>

            <div
                v-if="!workspaceGroups.length"
                class="mt-12 rounded-2xl border-2 border-dashed border-primary/25 px-6 py-14 text-center"
            >
                <h2 class="font-display text-xl font-semibold">No workspaces yet</h2>
                <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                    Boards live inside workspaces. Ask a teammate for an invite link to join theirs.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
