<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import workspaceRoutes from '@/routes/workspaces';
import type { BreadcrumbItem, User, Workspace, WorkspaceMember } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Link, UserRoundMinus, Users } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    workspace: Workspace;
    owner: User;
    members: WorkspaceMember[];
    inviteLink: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Workspace', href: workspaceRoutes.home(props.workspace.id).url },
    { title: 'Members', href: workspaceRoutes.members(props.workspace.id).url },
];

const copied = ref<'default' | 'empty' | null>(null);
const workspaceMembers = ref<WorkspaceMember[]>(props.members);

function copyInviteLink(from: 'default' | 'empty' = 'default') {
    navigator.clipboard.writeText(props?.inviteLink);
    copied.value = from;
    setTimeout(() => (copied.value = null), 2500);
}

function removeMember(memberId: number) {
    // for optimistic UI
    workspaceMembers.value = workspaceMembers.value.filter((member) => member.id !== memberId);

    router.delete(
        workspaceRoutes.members.user.destroy({
            workspace: props.workspace,
            user: memberId,
        }).url,
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}
</script>

<template>
    <Head :title="workspace.name + ' - Members'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10 py-12">
            <div class="max-w-2xl space-y-8">
                <!-- Header -->
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-bold tracking-tight">Workspace Members</h1>
                        <p class="text-sm text-muted-foreground">
                            Members can view, join, and create boards within this workspace.
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <Transition
                            enter-active-class="transition-all duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition-all duration-150"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <span
                                v-if="copied === 'default'"
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                            >
                                ✓ Link copied to clipboard!
                            </span>
                        </Transition>
                        <Button
                            class="cursor-pointer gap-2 shadow-sm"
                            size="sm"
                            variant="outline"
                            @click="copyInviteLink('default')"
                        >
                            <Link class="h-3.5 w-3.5 opacity-70" />
                            Invite with link
                        </Button>
                    </div>
                </div>

                <hr class="border-border/50" />

                <!-- Owner -->
                <div class="space-y-3">
                    <h3 class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase">Owner</h3>

                    <div
                        class="relative flex items-center gap-3 overflow-hidden rounded-xl border border-primary/15 bg-linear-to-br from-primary/4 to-accent/4 px-4 py-3.5"
                    >
                        <!-- accent bar -->
                        <div
                            class="absolute inset-y-0 left-0 w-[3px] rounded-l-xl bg-linear-to-b from-primary to-accent"
                        />

                        <Avatar class="h-10 w-10 ring-2 ring-primary/20">
                            <AvatarImage :alt="owner.name" :src="owner.avatar ?? ''" class="object-cover" />
                            <AvatarFallback
                                class="bg-linear-to-br from-primary to-accent text-xs font-semibold text-primary-foreground"
                            >
                                {{ owner.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex-1">
                            <p class="text-sm font-semibold">{{ owner.name }}</p>
                            <p class="text-xs font-medium text-primary">Workspace Owner</p>
                        </div>

                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-sm">👑</div>
                    </div>
                </div>

                <hr class="border-border/50" />

                <!-- Members -->
                <div class="space-y-3">
                    <h3
                        class="flex items-center gap-2 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Members
                        <span
                            class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium tracking-normal text-muted-foreground normal-case"
                        >
                            {{ workspaceMembers.length }}
                        </span>
                    </h3>

                    <div class="overflow-hidden rounded-xl border border-border/50 bg-card shadow-sm">
                        <div v-if="workspaceMembers.length > 0">
                            <div
                                v-for="(member, i) in workspaceMembers"
                                :key="member.id"
                                :style="{ animationDelay: `${i * 50}ms` }"
                                class="flex items-center gap-3 border-b border-border/40 px-4 py-3 transition-colors last:border-b-0 hover:bg-muted/40"
                            >
                                <Avatar class="h-8 w-8 shrink-0">
                                    <AvatarImage :alt="member.name" :src="member.avatar ?? ''" class="object-cover" />
                                    <AvatarFallback class="bg-muted text-xs font-semibold">
                                        {{ member.name.charAt(0) }}
                                    </AvatarFallback>
                                </Avatar>

                                <div class="flex min-w-0 flex-1 flex-col">
                                    <p class="truncate text-sm font-medium">{{ member.name }}</p>
                                    <p class="text-[11px] text-muted-foreground">Member</p>
                                </div>

                                <Button
                                    :aria-label="`Remove ${member.name} from workspace`"
                                    class="h-8 shrink-0 cursor-pointer gap-1.5 rounded-lg border-border/60 bg-muted/60 px-2.5 text-xs font-medium text-muted-foreground shadow-sm hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                                    size="sm"
                                    variant="outline"
                                    @click="removeMember(member.id)"
                                >
                                    <UserRoundMinus class="h-3.5 w-3.5" />
                                    <span class="hidden sm:inline">Remove</span>
                                </Button>
                            </div>
                        </div>
                        <!-- Empty state -->
                        <div v-else class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Users />
                                    </EmptyMedia>
                                    <EmptyTitle>No members yet</EmptyTitle>
                                    <EmptyDescription>
                                        This workspace is just you for now. Invite people to collaborate, manage boards,
                                        and get things done together.
                                    </EmptyDescription>
                                </EmptyHeader>
                                <EmptyContent>
                                    <Button class="gap-2" @click="copyInviteLink('empty')">
                                        <Link class="h-4 w-4" />
                                        {{ copied === 'empty' ? '✓ Link copied!' : 'Invite with link' }}
                                    </Button>
                                </EmptyContent>
                            </Empty>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
