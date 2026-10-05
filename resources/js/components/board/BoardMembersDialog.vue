<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import boardRoutes from '@/routes/boards';
import type { WorkspaceMember } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Crown, LogOut, Search, UserRoundMinus, UserRoundPlus } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const SEARCH_THRESHOLD = 6;

const props = defineProps<{
    boardId: string;
    boardName: string;
    workspaceName: string;
    owner: WorkspaceMember;
    members: WorkspaceMember[];
    canManageMembers: boolean;
    /** Workspace members who aren't on the board yet. Only sent to the owner. */
    addableMembers?: WorkspaceMember[];
}>();

const emit = defineEmits<{
    /** A request came back 404: the person or the whole board may be gone. The board page works out which. */
    notFound: [failureMessage: string];
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const { getInitials } = useInitials();

const pendingIds = ref(new Set<string>());
const isConfirmingLeave = ref(false);
const isLeaving = ref(false);
const search = ref('');
const announcement = ref('');
const bodyRef = ref<HTMLElement | null>(null);

const currentUserId = computed(() => page.props.auth.user.id);
const people = computed(() => [props.owner, ...props.members]);
const candidates = computed(() => props.addableMembers ?? []);
const showSearch = computed(() => candidates.value.length > SEARCH_THRESHOLD);

const filteredCandidates = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();

    if (!query) {
        return candidates.value;
    }

    return candidates.value.filter(
        (person) =>
            person.name.toLocaleLowerCase().includes(query) || person.email?.toLocaleLowerCase().includes(query),
    );
});

watch(open, (isOpen) => {
    if (!isOpen) {
        search.value = '';
        isConfirmingLeave.value = false;
    }
});

/** Start on the list itself rather than on the first Remove button. */
function onOpenAutoFocus(event: Event) {
    event.preventDefault();
    bodyRef.value?.focus();
}

function isOwner(person: WorkspaceMember) {
    return person.id === props.owner.id;
}

function isCurrentUser(person: WorkspaceMember) {
    return person.id === currentUserId.value;
}

function announce(message: string) {
    announcement.value = '';
    nextTick(() => (announcement.value = message));
}

function focusById(id: string) {
    nextTick(() => document.getElementById(id)?.focus());
}

function setPending(person: WorkspaceMember, isPending: boolean) {
    const ids = new Set(pendingIds.value);

    if (isPending) {
        ids.add(person.id);
    } else {
        ids.delete(person.id);
    }

    pendingIds.value = ids;
}

/**
 * Explain a failed request. A 404 goes to the board page, which checks whether the board itself is
 * still reachable before saying anything.
 */
function handleFailure(failureMessage: string, retry: () => void, status?: number, validationMessage?: string) {
    if (status === 404) {
        emit('notFound', failureMessage);

        return;
    }

    const descriptions: Record<number, string> = {
        403: 'Only the workspace owner can add or remove board members.',
        419: 'Your session expired. Refresh the page, then try again.',
    };
    const canRetry = validationMessage === undefined && (status === undefined || !(status in descriptions));

    toast.error(failureMessage, {
        description:
            validationMessage ??
            (status === undefined
                ? 'Check your connection, then try again.'
                : (descriptions[status] ?? 'Something went wrong on our end. Try again in a moment.')),
        action: canRetry ? { label: 'Try again', onClick: retry } : undefined,
    });
}

function changeMembership(
    method: 'post' | 'delete',
    url: string,
    person: WorkspaceMember,
    successMessage: string,
    failureMessage: string,
    retry: () => void,
) {
    if (pendingIds.value.has(person.id)) {
        return;
    }

    setPending(person, true);

    router.visit(url, {
        method,
        data: method === 'post' ? { user_id: person.id } : {},
        async: true,
        only: ['members', 'addableMembers'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => announce(successMessage),
        onError: (errors) => handleFailure(failureMessage, retry, 422, Object.values(errors)[0]),
        onHttpException: (response) => {
            handleFailure(failureMessage, retry, response.status);

            return false;
        },
        onNetworkError: () => {
            handleFailure(failureMessage, retry);

            return false;
        },
        onFinish: () => setPending(person, false),
    });
}

function addMember(person: WorkspaceMember) {
    changeMembership(
        'post',
        boardRoutes.members.store(props.boardId).url,
        person,
        `Added ${person.name} to ${props.boardName}.`,
        `Couldn’t add ${person.name}`,
        () => addMember(person),
    );
}

function removeMember(person: WorkspaceMember) {
    changeMembership(
        'delete',
        boardRoutes.members.destroy({ board: props.boardId, member: person.id }).url,
        person,
        `Removed ${person.name} from ${props.boardName}.`,
        `Couldn’t remove ${person.name}`,
        () => removeMember(person),
    );
}

function askToLeave() {
    isConfirmingLeave.value = true;
    focusById('cancel-leave-board');
}

function cancelLeave() {
    isConfirmingLeave.value = false;
    focusById('leave-board');
}

function leaveBoard() {
    if (isLeaving.value) {
        return;
    }

    const boardName = props.boardName;
    const fail = (status?: number) => {
        isLeaving.value = false;
        handleFailure('Couldn’t leave the board', leaveBoard, status);
    };

    router.delete(boardRoutes.leave(props.boardId).url, {
        replace: true,
        onStart: () => (isLeaving.value = true),
        onSuccess: () =>
            toast.success(`You left “${boardName}”`, {
                description: 'The workspace owner can add you back.',
            }),
        onHttpException: (response) => {
            fail(response.status);

            return false;
        },
        onNetworkError: () => {
            fail();

            return false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="flex max-h-[calc(100dvh-5rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg"
            @open-auto-focus="onOpenAutoFocus"
        >
            <DialogHeader class="gap-1.5 border-b px-5 pt-5 pr-12 pb-4 text-left sm:px-6">
                <DialogTitle class="flex items-center gap-2 text-xl font-semibold tracking-tight">
                    Board members
                    <span
                        class="rounded-full bg-muted px-2 py-0.5 font-sans text-xs font-medium text-muted-foreground tabular-nums"
                    >
                        {{ people.length }}
                    </span>
                </DialogTitle>
                <DialogDescription>
                    Only the people here can see {{ boardName }}. The workspace owner is on every board.
                </DialogDescription>
            </DialogHeader>

            <div
                ref="bodyRef"
                class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-5 py-4 outline-none sm:px-6"
                tabindex="-1"
            >
                <section aria-labelledby="board-people-heading">
                    <h3 id="board-people-heading" class="sr-only">On this board</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="person in people"
                            :key="person.id"
                            :class="isOwner(person) ? 'border-primary/20' : ''"
                            class="rounded-xl border bg-card px-3 py-2.5"
                        >
                            <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3">
                                <Avatar class="size-9 ring-1 ring-border">
                                    <AvatarImage :src="person.avatar ?? ''" alt="" class="object-cover" />
                                    <AvatarFallback class="bg-blush text-xs font-semibold text-blush-foreground">
                                        {{ getInitials(person.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <div class="min-w-0">
                                    <p class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="min-w-0 truncate text-sm font-medium">{{ person.name }}</span>
                                        <span
                                            v-if="isCurrentUser(person)"
                                            class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                                        >
                                            You
                                        </span>
                                        <span
                                            v-if="isOwner(person)"
                                            class="inline-flex shrink-0 items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                                        >
                                            <Crown aria-hidden="true" class="size-3" />
                                            Owner
                                        </span>
                                    </p>
                                    <p v-if="person.email" class="truncate text-xs text-muted-foreground">
                                        {{ person.email }}
                                    </p>
                                </div>

                                <Button
                                    v-if="canManageMembers && !isOwner(person)"
                                    :aria-label="`Remove ${person.name} from ${boardName}`"
                                    :disabled="pendingIds.has(person.id)"
                                    class="cursor-pointer text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    size="sm"
                                    variant="ghost"
                                    @click="removeMember(person)"
                                >
                                    <UserRoundMinus aria-hidden="true" />
                                    <span class="hidden sm:inline">
                                        {{ pendingIds.has(person.id) ? 'Removing…' : 'Remove' }}
                                    </span>
                                </Button>
                                <Button
                                    v-else-if="isCurrentUser(person) && !isOwner(person) && !isConfirmingLeave"
                                    id="leave-board"
                                    :aria-label="`Leave ${boardName}`"
                                    class="cursor-pointer text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    size="sm"
                                    variant="ghost"
                                    @click="askToLeave"
                                >
                                    <LogOut aria-hidden="true" />
                                    <span class="hidden sm:inline">Leave</span>
                                </Button>
                            </div>

                            <div
                                v-if="isCurrentUser(person) && isConfirmingLeave"
                                aria-labelledby="confirm-leave-board"
                                class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-lg border border-destructive/20 bg-destructive/5 p-3"
                                role="group"
                                @keydown.esc.stop.prevent="cancelLeave"
                            >
                                <p id="confirm-leave-board" class="text-sm">
                                    Leave {{ boardName }}?
                                    <span class="text-muted-foreground">
                                        You stay in {{ workspaceName }}, but only the owner can add you back.
                                    </span>
                                </p>
                                <div class="ml-auto flex gap-2">
                                    <Button
                                        id="cancel-leave-board"
                                        :disabled="isLeaving"
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="outline"
                                        @click="cancelLeave"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        :disabled="isLeaving"
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="destructive"
                                        @click="leaveBoard"
                                    >
                                        {{ isLeaving ? 'Leaving…' : 'Leave board' }}
                                    </Button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section v-if="canManageMembers" aria-labelledby="board-candidates-heading">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 id="board-candidates-heading" class="text-sm font-semibold">
                            Add people from {{ workspaceName }}
                        </h3>
                        <div v-if="showSearch" class="relative w-full sm:w-52">
                            <Search
                                aria-hidden="true"
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                aria-label="Search workspace members"
                                class="h-8 pl-9"
                                placeholder="Search by name or email"
                                type="search"
                            />
                        </div>
                    </div>

                    <p
                        v-if="!candidates.length"
                        class="rounded-xl border border-dashed px-4 py-3 text-sm text-muted-foreground"
                    >
                        Everyone in {{ workspaceName }} is already on this board. Invite more people from the workspace
                        members page.
                    </p>
                    <p
                        v-else-if="!filteredCandidates.length"
                        class="rounded-xl border border-dashed px-4 py-3 text-sm text-muted-foreground"
                    >
                        No one matches “{{ search.trim() }}”.
                    </p>
                    <ul v-else class="space-y-1">
                        <li
                            v-for="person in filteredCandidates"
                            :key="person.id"
                            class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-lg px-2 py-1.5"
                        >
                            <Avatar class="size-8">
                                <AvatarImage :src="person.avatar ?? ''" alt="" class="object-cover" />
                                <AvatarFallback class="bg-muted text-xs font-semibold text-muted-foreground">
                                    {{ getInitials(person.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <p class="truncate text-sm">{{ person.name }}</p>
                                <p v-if="person.email" class="truncate text-xs text-muted-foreground">
                                    {{ person.email }}
                                </p>
                            </div>
                            <Button
                                :aria-label="`Add ${person.name} to ${boardName}`"
                                :disabled="pendingIds.has(person.id)"
                                class="cursor-pointer"
                                size="sm"
                                variant="outline"
                                @click="addMember(person)"
                            >
                                <UserRoundPlus aria-hidden="true" />
                                <span class="hidden sm:inline">
                                    {{ pendingIds.has(person.id) ? 'Adding…' : 'Add' }}
                                </span>
                            </Button>
                        </li>
                    </ul>
                </section>
            </div>

            <p aria-live="polite" class="sr-only" role="status">{{ announcement }}</p>
        </DialogContent>
    </Dialog>
</template>
