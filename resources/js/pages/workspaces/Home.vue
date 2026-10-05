<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardCardPopover from '@/components/board/BoardCardPopover.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import ArchivedBoardsDialog from '@/components/workspace/ArchivedBoardsDialog.vue';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { home, members as workspaceMembers } from '@/routes/workspaces';
import { favorite } from '@/routes/workspaces/boards';
import type { Board, BreadcrumbItem, Workspace, WorkspaceMember } from '@/types';
import { Deferred, Head, Link, router, useHttp } from '@inertiajs/vue3';
import { Archive, Check, Link as LinkIcon, Star } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const AVATAR_CAP = 4;
const COPIED_RESET_MS = 2500;

const props = defineProps<{
    workspace: Workspace;
    members: WorkspaceMember[];
    boards?: Board[];
    canInvite: boolean;
    /** Only sent to the owner. */
    inviteLink?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Workspace', href: home(props.workspace.id).url },
    { title: 'Home', href: home(props.workspace.id).url },
];

const { getInitials } = useInitials();
const http = useHttp();

const boards = ref<Board[]>(props.boards ?? []);
const isInviteLinkCopied = ref(false);
const showArchivedBoardsDialog = ref(false);
let copiedResetTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => props.boards,
    (value) => {
        boards.value = value ?? [];
    },
);

const starredBoards = computed(() => boards.value.filter((board) => board.is_favorited));

const visibleMembers = computed(() => props.members.slice(0, AVATAR_CAP));

const hiddenMemberCount = computed(() => Math.max(props.members.length - AVATAR_CAP, 0));

const memberSummary = computed(() => {
    const count = props.members.length;

    return count === 0 ? 'No members yet' : `${count} ${count === 1 ? 'member' : 'members'}`;
});

async function copyInviteLink() {
    if (!props.inviteLink) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.inviteLink);
    } catch {
        toast.error('Could not copy the invite link. Allow clipboard access and try again.');

        return;
    }

    isInviteLinkCopied.value = true;
    clearTimeout(copiedResetTimer);
    copiedResetTimer = setTimeout(() => (isInviteLinkCopied.value = false), COPIED_RESET_MS);
}

function handleUnarchiveBoard(board: Board) {
    const index = boards.value.findIndex((existing) => new Date(existing.created_at) > new Date(board.created_at));

    if (index === -1) {
        boards.value.push(board);
    } else {
        boards.value.splice(index, 0, board);
    }
}

function handleStarBoard(board: Board, isStarred: boolean) {
    board.is_favorited = isStarred;

    const rollback = () => {
        toast.error('Could not update the star. Try again.');
        board.is_favorited = !isStarred;
    };

    http.post(favorite({ workspace: props.workspace.id, board: board.id }).url, {
        // Refresh the sidebar's Starred section; if that fails it catches up on the next visit.
        onSuccess: () =>
            router.reload({ only: ['starredBoards'], onHttpException: () => false, onNetworkError: () => false }),
        onError: rollback,
        onHttpException: rollback,
        onNetworkError: rollback,
    }).catch(() => {});
}

onBeforeUnmount(() => clearTimeout(copiedResetTimer));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="workspace.name" />

        <div class="mx-auto w-full max-w-7xl px-4 pt-8 pb-16 sm:px-6 sm:pt-10 lg:px-10">
            <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div
                    class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-3 sm:gap-x-5 sm:gap-y-1.5"
                >
                    <Avatar class="size-12 rounded-xl ring-1 ring-border sm:row-span-2 sm:size-16 sm:rounded-2xl">
                        <AvatarImage :src="workspace.logo ?? ''" alt="" class="object-cover" />
                        <AvatarFallback
                            class="rounded-xl bg-blush text-lg font-semibold text-blush-foreground sm:rounded-2xl sm:text-xl"
                        >
                            {{ getInitials(workspace.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <h1 class="text-2xl font-semibold tracking-tight break-words sm:self-end sm:text-4xl">
                        {{ workspace.name }}
                    </h1>
                    <p
                        v-if="workspace.description"
                        class="col-span-2 line-clamp-3 max-w-prose text-sm text-muted-foreground sm:col-span-1 sm:col-start-2 sm:self-start"
                    >
                        {{ workspace.description }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <Link
                        :href="workspaceMembers(workspace.id)"
                        class="flex min-h-8 items-center gap-2 rounded-full py-0.5 pr-3 pl-0.5 text-xs font-medium transition-colors duration-200 outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:min-h-9 sm:text-sm"
                    >
                        <span v-if="members.length" aria-hidden="true" class="flex -space-x-2">
                            <Avatar
                                v-for="member in visibleMembers"
                                :key="member.id"
                                class="size-7 ring-2 ring-background sm:size-8"
                            >
                                <AvatarImage :src="member.avatar ?? ''" alt="" class="object-cover" />
                                <AvatarFallback class="bg-blush text-xs font-semibold text-blush-foreground">
                                    {{ getInitials(member.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                v-if="hiddenMemberCount"
                                class="flex size-7 items-center justify-center rounded-full bg-muted text-[10px] font-semibold text-muted-foreground tabular-nums ring-2 ring-background sm:size-8 sm:text-xs"
                            >
                                +{{ hiddenMemberCount }}
                            </span>
                        </span>
                        <span :class="{ 'pl-2.5': !members.length }" class="tabular-nums">{{ memberSummary }}</span>
                    </Link>

                    <Button
                        v-if="canInvite"
                        :disabled="!inviteLink"
                        class="cursor-pointer rounded-full text-xs sm:h-9 sm:px-4 sm:text-sm"
                        size="sm"
                        variant="outline"
                        @click="copyInviteLink"
                    >
                        <Check v-if="isInviteLinkCopied" class="size-3.5 text-primary" aria-hidden="true" />
                        <LinkIcon v-else class="size-3.5" aria-hidden="true" />
                        {{ isInviteLinkCopied ? 'Link copied' : 'Copy invite link' }}
                    </Button>
                    <span v-if="canInvite" aria-live="polite" class="sr-only">
                        {{ isInviteLinkCopied ? 'Invite link copied to clipboard' : '' }}
                    </span>
                </div>
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

            <section aria-labelledby="boards-heading" class="mt-12">
                <div class="mb-6 flex items-center justify-between gap-3 border-b pb-3">
                    <h2
                        id="boards-heading"
                        class="flex items-baseline gap-2 text-xl font-semibold tracking-tight sm:text-2xl"
                    >
                        Boards
                        <span
                            v-if="boards.length"
                            class="font-sans text-sm font-medium text-muted-foreground tabular-nums"
                        >
                            {{ boards.length }}
                        </span>
                    </h2>
                    <Button
                        class="cursor-pointer text-muted-foreground"
                        size="sm"
                        variant="ghost"
                        @click="showArchivedBoardsDialog = true"
                    >
                        <Archive aria-hidden="true" />
                        Archived<span class="sr-only"> boards</span>
                    </Button>
                </div>

                <Deferred data="boards">
                    <template #fallback>
                        <div
                            aria-busy="true"
                            aria-label="Loading boards"
                            class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
                        >
                            <div v-for="i in 4" :key="i" class="overflow-hidden rounded-2xl border bg-card">
                                <Skeleton class="h-24 rounded-none bg-blush/60" />
                                <div class="space-y-2 px-4 py-3">
                                    <Skeleton :class="i % 2 ? 'w-2/3' : 'w-1/2'" class="h-3.5" />
                                    <Skeleton class="h-3 w-1/3" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <p v-if="!boards.length" class="mb-4 text-sm text-muted-foreground">
                        No boards yet. Create the first one to start planning.
                    </p>
                    <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                        <BoardCard
                            v-for="board in boards"
                            :key="board.id"
                            :board="board"
                            @star-board="handleStarBoard"
                        />
                        <BoardCardPopover :workspace-id="workspace.id" />
                    </div>
                </Deferred>
            </section>
        </div>

        <ArchivedBoardsDialog
            v-model:open="showArchivedBoardsDialog"
            :workspace="workspace"
            @unarchive-board="handleUnarchiveBoard"
        />
    </AppLayout>
</template>
