<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import workspaceRoutes from '@/routes/workspaces';
import type { BreadcrumbItem, Workspace, WorkspaceMember } from '@/types';
import { Deferred, Head, router, usePage } from '@inertiajs/vue3';
import { Check, Crown, Link as LinkIcon, Search, UserRoundMinus, UserRoundPlus } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const SEARCH_THRESHOLD = 6;
const COPIED_RESET_MS = 2500;

const props = defineProps<{
    workspace: Workspace;
    owner: WorkspaceMember;
    members: WorkspaceMember[];
    canManageMembers: boolean;
    inviteLink?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Workspace', href: workspaceRoutes.home(props.workspace.id).url },
    { title: 'Members', href: workspaceRoutes.members(props.workspace.id).url },
];

const page = usePage();
const { getInitials } = useInitials();
const joinedFormat = new Intl.DateTimeFormat(undefined, { month: 'short', year: 'numeric' });

const workspaceMembers = ref<WorkspaceMember[]>([...props.members]);
const pendingRemovalIds = new Set<string>();
const search = ref('');
const confirmingRemovalId = ref<string | null>(null);
const isInviteLinkCopied = ref(false);
const announcement = ref('');
const listRef = ref<HTMLElement | null>(null);
let copiedResetTimer: ReturnType<typeof setTimeout> | undefined;

/**
 * Server refreshes replace the list, but members whose removal is still in
 * flight stay hidden so they don't flash back in before the request settles.
 */
watch(
    () => props.members,
    (members) => {
        workspaceMembers.value = members.filter((member) => !pendingRemovalIds.has(member.id));
    },
);

const currentUserId = computed(() => page.props.auth.user.id);

const people = computed(() => [props.owner, ...workspaceMembers.value]);

const showSearch = computed(() => people.value.length > SEARCH_THRESHOLD);

const normalizedSearch = computed(() => search.value.trim().toLocaleLowerCase());

const visiblePeople = computed(() => {
    if (!normalizedSearch.value) {
        return people.value;
    }

    return people.value.filter(
        (person) =>
            person.name.toLocaleLowerCase().includes(normalizedSearch.value) ||
            person.email?.toLocaleLowerCase().includes(normalizedSearch.value),
    );
});

watch(showSearch, (isShown) => {
    if (!isShown) {
        search.value = '';
    }
});

function isOwner(person: WorkspaceMember) {
    return person.id === props.owner.id;
}

function isCurrentUser(person: WorkspaceMember) {
    return person.id === currentUserId.value;
}

function canRemove(person: WorkspaceMember) {
    return props.canManageMembers && !isOwner(person);
}

function formatJoinedAt(person: WorkspaceMember) {
    if (isOwner(person)) {
        return 'Created this workspace';
    }

    return person.joined_at ? `Joined ${joinedFormat.format(new Date(person.joined_at))}` : '';
}

function announce(message: string) {
    announcement.value = '';
    nextTick(() => (announcement.value = message));
}

function focusById(id: string) {
    nextTick(() => document.getElementById(id)?.focus());
}

async function copyInviteLink() {
    if (!props.inviteLink) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.inviteLink);
    } catch {
        toast.error('Couldn’t copy the invite link', {
            description: 'Allow clipboard access, or select the link and copy it yourself.',
        });

        return;
    }

    isInviteLinkCopied.value = true;
    announce('Invite link copied to clipboard.');
    clearTimeout(copiedResetTimer);
    copiedResetTimer = setTimeout(() => (isInviteLinkCopied.value = false), COPIED_RESET_MS);
}

function selectInviteLink(event: FocusEvent) {
    (event.target as HTMLInputElement).select();
}

function askToRemove(member: WorkspaceMember) {
    confirmingRemovalId.value = member.id;
    focusById(`cancel-remove-${member.id}`);
}

function cancelRemoval(member: WorkspaceMember) {
    confirmingRemovalId.value = null;
    focusById(`remove-${member.id}`);
}

/**
 * Takes the row out right away, then keeps keyboard focus in the list by
 * moving it to the next remove button, or to the list itself when none is left.
 */
async function removeRow(member: WorkspaceMember) {
    const index = workspaceMembers.value.findIndex((existing) => existing.id === member.id);
    const removableIndex = visiblePeople.value.filter(canRemove).findIndex((existing) => existing.id === member.id);

    if (index === -1) {
        return -1;
    }

    confirmingRemovalId.value = null;
    pendingRemovalIds.add(member.id);
    workspaceMembers.value.splice(index, 1);
    await nextTick();

    const removeButtons = listRef.value?.querySelectorAll<HTMLElement>(
        '.member-row:not(.member-row-leave-active) [data-remove-button]',
    );
    const nextFocus = removeButtons?.[Math.min(removableIndex, removeButtons.length - 1)];
    (nextFocus ?? listRef.value)?.focus();

    return index;
}

function handleRemovalFailure(member: WorkspaceMember, index: number, status?: number) {
    if (status === 404) {
        announce(`${member.name} is no longer in ${props.workspace.name}.`);

        return;
    }

    if (!workspaceMembers.value.some((existing) => existing.id === member.id)) {
        workspaceMembers.value.splice(Math.min(index, workspaceMembers.value.length), 0, member);
    }

    const descriptions: Record<number, string> = {
        403: 'Only the workspace owner can remove members.',
        419: 'Your session expired. Refresh the page, then try again.',
    };
    const canRetry = status === undefined || !(status in descriptions);

    toast.error(`Couldn’t remove ${member.name}`, {
        description:
            status === undefined
                ? 'Check your connection, then try again.'
                : (descriptions[status] ?? 'Something went wrong on our end. Try again in a moment.'),
        action: canRetry ? { label: 'Try again', onClick: () => removeMember(member) } : undefined,
    });
}

async function removeMember(member: WorkspaceMember) {
    const index = await removeRow(member);

    if (index === -1) {
        return;
    }

    router.delete(workspaceRoutes.members.user.destroy({ workspace: props.workspace.id, user: member.id }).url, {
        async: true,
        only: ['members'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => announce(`Removed ${member.name} from ${props.workspace.name}.`),
        onError: () => handleRemovalFailure(member, index, 422),
        onHttpException: (response) => {
            handleRemovalFailure(member, index, response.status);

            return false;
        },
        onNetworkError: () => {
            handleRemovalFailure(member, index);

            return false;
        },
        onFinish: () => pendingRemovalIds.delete(member.id),
    });
}

onBeforeUnmount(() => clearTimeout(copiedResetTimer));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${workspace.name} members`">
            <link
                head-key="font-fraunces"
                href="https://fonts.bunny.net/css?family=fraunces:500,600"
                rel="stylesheet"
            />
        </Head>

        <div class="mx-auto w-full max-w-3xl px-4 pt-8 pb-16 sm:px-6 sm:pt-10 lg:px-10">
            <header>
                <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Members</h1>
                <p class="mt-1.5 max-w-prose text-sm text-muted-foreground">
                    Everyone in <span class="font-medium text-foreground">{{ workspace.name }}</span> can view, create
                    and join its boards.
                </p>
            </header>

            <section
                aria-labelledby="invite-heading"
                class="mt-8 rounded-2xl border border-primary/15 bg-blush/40 p-4 sm:p-5"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-background text-primary ring-1 ring-primary/15"
                        aria-hidden="true"
                    >
                        <UserRoundPlus class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <h2 id="invite-heading" class="font-semibold">Invite people</h2>
                        <p class="text-sm text-muted-foreground">
                            Anyone with this link can join {{ workspace.name }}.
                        </p>
                    </div>
                </div>

                <Deferred data="inviteLink">
                    <template #fallback>
                        <div aria-busy="true" aria-label="Preparing invite link" class="mt-4 flex gap-2">
                            <Skeleton class="h-9 flex-1 bg-background/70" />
                            <Skeleton class="h-9 w-11 bg-background/70 sm:w-28" />
                        </div>
                    </template>

                    <div class="mt-4 flex gap-2">
                        <Input
                            :model-value="inviteLink"
                            aria-label="Invite link"
                            class="min-w-0 flex-1 bg-background text-muted-foreground"
                            readonly
                            @focus="selectInviteLink"
                        />
                        <Button
                            :aria-label="isInviteLinkCopied ? 'Invite link copied' : 'Copy invite link'"
                            :disabled="!inviteLink"
                            class="shrink-0 cursor-pointer sm:min-w-28"
                            @click="copyInviteLink"
                        >
                            <Check v-if="isInviteLinkCopied" aria-hidden="true" />
                            <LinkIcon v-else aria-hidden="true" />
                            <span class="hidden sm:inline">{{ isInviteLinkCopied ? 'Copied' : 'Copy link' }}</span>
                        </Button>
                    </div>
                </Deferred>
            </section>

            <section aria-labelledby="people-heading" class="mt-10">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <h2
                        id="people-heading"
                        class="flex items-baseline gap-2 font-display text-xl font-semibold tracking-tight sm:text-2xl"
                    >
                        People
                        <span class="font-sans text-sm font-medium text-muted-foreground tabular-nums">
                            {{ people.length }}
                        </span>
                    </h2>

                    <div v-if="showSearch" class="relative w-full sm:w-64">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="search"
                            aria-label="Search members"
                            class="pl-9"
                            placeholder="Search by name or email"
                            type="search"
                        />
                    </div>
                </div>

                <div ref="listRef" class="outline-none" tabindex="-1">
                    <TransitionGroup
                        aria-labelledby="people-heading"
                        class="relative space-y-2"
                        name="member-row"
                        tag="ul"
                    >
                        <li
                            v-for="person in visiblePeople"
                            :key="person.id"
                            :class="
                                confirmingRemovalId === person.id
                                    ? 'border-destructive/40 bg-destructive/5'
                                    : isOwner(person)
                                      ? 'border-primary/20'
                                      : ''
                            "
                            class="member-row grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-3 rounded-xl border bg-card px-3 py-3 sm:gap-x-4 sm:px-4"
                        >
                            <Avatar class="size-10 ring-1 ring-border">
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
                                        <Crown class="size-3" aria-hidden="true" />
                                        Owner
                                    </span>
                                </p>
                                <p class="truncate text-xs text-muted-foreground">{{ person.email }}</p>
                                <p v-if="formatJoinedAt(person)" class="text-xs text-muted-foreground sm:hidden">
                                    {{ formatJoinedAt(person) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span
                                    v-if="formatJoinedAt(person)"
                                    class="hidden text-xs whitespace-nowrap text-muted-foreground sm:inline"
                                >
                                    {{ formatJoinedAt(person) }}
                                </span>
                                <Button
                                    v-if="canRemove(person) && confirmingRemovalId !== person.id"
                                    :id="`remove-${person.id}`"
                                    :aria-label="`Remove ${person.name} from ${workspace.name}`"
                                    class="cursor-pointer text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    data-remove-button
                                    size="sm"
                                    variant="ghost"
                                    @click="askToRemove(person)"
                                >
                                    <UserRoundMinus aria-hidden="true" />
                                    <span class="hidden sm:inline">Remove</span>
                                </Button>
                            </div>

                            <div
                                v-if="confirmingRemovalId === person.id"
                                :aria-labelledby="`confirm-remove-${person.id}`"
                                class="col-span-full flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-destructive/20 pt-3"
                                role="group"
                                @keydown.esc.stop.prevent="cancelRemoval(person)"
                            >
                                <p :id="`confirm-remove-${person.id}`" class="text-sm">
                                    Remove {{ person.name }}?
                                    <span class="text-muted-foreground">They’ll lose access to its boards.</span>
                                </p>
                                <div class="ml-auto flex gap-2">
                                    <Button
                                        :id="`cancel-remove-${person.id}`"
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="outline"
                                        @click="cancelRemoval(person)"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="destructive"
                                        @click="removeMember(person)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </li>
                    </TransitionGroup>
                </div>

                <Transition name="member-empty">
                    <p v-if="!visiblePeople.length" class="py-10 text-center text-sm text-muted-foreground">
                        No one matches “{{ search.trim() }}”.
                    </p>
                    <div
                        v-else-if="!workspaceMembers.length && !normalizedSearch"
                        class="mt-4 rounded-2xl border-2 border-dashed border-primary/25 px-6 py-8 text-center"
                    >
                        <p class="font-medium">It’s just {{ canManageMembers ? 'you' : owner.name }} for now</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                            Share the invite link above to bring people into {{ workspace.name }}.
                        </p>
                    </div>
                </Transition>

                <p aria-live="polite" class="sr-only">{{ announcement }}</p>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.member-row {
    transition:
        background-color 200ms ease,
        border-color 200ms ease;
}

.member-row-move,
.member-row-enter-active,
.member-row-leave-active {
    transition:
        transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
        opacity 240ms ease,
        background-color 200ms ease,
        border-color 200ms ease;
}

.member-row-leave-active {
    position: absolute;
    inset-inline: 0;
    pointer-events: none;
    border-color: color-mix(in oklab, var(--destructive) 45%, transparent);
    background-color: color-mix(in oklab, var(--destructive) 8%, var(--card));
}

.member-row-enter-from {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

.member-row-leave-to {
    opacity: 0;
    transform: translateX(-1.5rem);
}

.member-empty-enter-active {
    transition: opacity 200ms ease 160ms;
}

.member-empty-enter-from {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .member-row-move,
    .member-row-enter-active,
    .member-row-leave-active {
        transition: opacity 150ms ease;
    }

    .member-row-enter-from,
    .member-row-leave-to {
        transform: none;
    }
}
</style>
