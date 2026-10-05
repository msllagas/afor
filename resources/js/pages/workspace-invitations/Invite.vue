<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { login, register } from '@/routes';
import workspaceInvitationsRoutes from '@/routes/workspace-invitations';
import type { Invitation } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, LayoutDashboard, Users } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    invitation: Invitation;
    savedToken?: boolean;
}>();

const { getInitials } = useInitials();

const isAccepting = ref(false);

function acceptInvitation() {
    router.post(
        workspaceInvitationsRoutes.accept({
            workspace: props.invitation.workspace.id,
            token: props.invitation.token,
        }),
        {},
        {
            onStart: () => (isAccepting.value = true),
            onFinish: () => (isAccepting.value = false),
        },
    );
}
</script>

<template>
    <main
        class="relative flex min-h-dvh flex-col items-center justify-center gap-6 overflow-hidden bg-background px-4 py-10 text-foreground"
    >
        <Head title="Workspace invitation" />

        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-linear-to-b from-primary/10 to-transparent dark:from-primary/15"
        />

        <section
            aria-labelledby="invitation-heading"
            class="relative w-full max-w-md overflow-hidden rounded-3xl border bg-card text-center shadow-xl ring-1 ring-primary/10 dark:ring-primary/15"
        >
            <div class="space-y-7 p-6 sm:p-9">
                <div class="space-y-5">
                    <div class="relative mx-auto w-fit">
                        <div
                            aria-hidden="true"
                            class="flex size-20 items-center justify-center rounded-2xl bg-primary/15 text-3xl font-semibold text-primary ring-1 ring-primary/20"
                        >
                            {{ getInitials(invitation.workspace.name) }}
                        </div>
                        <span
                            aria-hidden="true"
                            class="absolute -right-2 -bottom-2 flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground ring-4 ring-card"
                        >
                            <Users class="size-4" />
                        </span>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-medium tracking-widest text-muted-foreground uppercase">
                            Workspace invitation
                        </p>
                        <h1 id="invitation-heading" class="text-2xl leading-snug font-semibold text-balance">
                            Join
                            <span class="break-words text-primary">{{ invitation.workspace.name }}</span>
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">{{ invitation.inviter.name }}</span>
                            invited you to work together on their boards.
                        </p>
                    </div>
                </div>

                <div v-if="$page.props.auth.user" class="space-y-3">
                    <Button
                        :disabled="isAccepting"
                        class="h-11 w-full cursor-pointer gap-2 font-semibold"
                        size="lg"
                        @click="acceptInvitation"
                    >
                        {{ isAccepting ? 'Joining…' : 'Accept invitation' }}
                        <ArrowRight v-if="!isAccepting" aria-hidden="true" class="size-4" />
                    </Button>
                    <p class="flex items-center justify-center gap-1.5 text-xs text-muted-foreground">
                        <LayoutDashboard aria-hidden="true" class="size-3.5" />
                        Joining as
                        <span class="font-medium text-foreground">{{ $page.props.auth.user.name }}</span>
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <Button as-child class="h-11 w-full font-semibold" size="lg">
                        <Link :href="register()">Create an account</Link>
                    </Button>
                    <Button as-child class="h-11 w-full font-semibold" size="lg" variant="outline">
                        <Link :href="login()">I already have an account</Link>
                    </Button>
                    <p class="text-xs text-muted-foreground">Sign in or sign up to join this workspace.</p>
                </div>
            </div>
        </section>
    </main>
</template>
