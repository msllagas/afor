<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import workspaceRoutes from '@/routes/workspaces';
import type { BreadcrumbItem, User, Workspace, WorkspaceMember } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Link } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    workspace: Workspace;
    owner: User;
    members: WorkspaceMember[];
    inviteLink: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workspace',
        href: workspaceRoutes.home(props.workspace.id).url,
    },
    {
        title: 'Members',
        href: workspaceRoutes.members(props.workspace.id).url,
    },
];

const copied = ref(false);
function copyInviteLink() {
    navigator.clipboard.writeText(props?.inviteLink);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2500);
}
</script>

<template>
    <Head :title="workspace.name + ' - Members'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10 max-w-2xl">
                <div class="space-y-1">
                    <h1 class="text-lg font-semibold tracking-tight">Workspace members</h1>
                    <p class="text-sm text-muted-foreground">
                        Members can view, join, and create boards within this workspace.
                    </p>
                </div>

                <div class="mt-4 space-y-2">
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-200"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <p v-if="copied" class="text-xs font-medium text-emerald-500">✓ Link copied to clipboard!</p>
                    </Transition>

                    <Button size="sm" variant="outline" class="cursor-pointer gap-2 shadow-sm" @click="copyInviteLink">
                        <Link class="h-3.5 w-3.5" />
                        Invite with link
                    </Button>
                </div>

                <hr class="my-8 border-border/50" />

                <div class="mb-8 space-y-3">
                    <h3 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Owner</h3>
                    <div class="flex items-center gap-3">
                        <Avatar class="h-9 w-9">
                            <AvatarImage :alt="owner.name" :src="owner.avatar ?? ''" class="object-cover" />
                            <AvatarFallback class="bg-muted text-xs font-medium">
                                {{ owner.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <p class="text-sm font-medium">{{ owner.name }}</p>
                            <p class="text-xs text-muted-foreground">Owner</p>
                        </div>
                    </div>
                </div>

                <hr class="my-8 border-border/50" />

                <div class="space-y-3">
                    <h3 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                        Members
                        <span
                            class="ml-1 rounded-full bg-muted px-2 py-0.5 text-xs font-medium tracking-normal text-muted-foreground normal-case"
                            >{{ members.length }}</span
                        >
                    </h3>
                    <div class="divide-y divide-border/50 rounded-xl border border-border/50">
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
                        >
                            <Avatar class="h-8 w-8">
                                <AvatarImage :alt="member.name" :src="member.avatar ?? ''" class="object-cover" />
                                <AvatarFallback class="bg-muted text-xs font-medium">
                                    {{ member.name.charAt(0) }}
                                </AvatarFallback>
                            </Avatar>
                            <p class="text-sm font-medium">{{ member.name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
