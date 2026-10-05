<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import workspaceInvitationsRoutes from '@/routes/workspace-invitations';
import type { Invitation } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    invitation: Invitation;
    savedToken?: boolean;
}>();

function acceptInvitation() {
    router.post(
        workspaceInvitationsRoutes.accept({
            workspace: props.invitation.workspace.id,
            token: props.invitation.token,
        }),
    );
}

function handleLogin() {
    console.log('handle login here');
}
function handleRegister() {
    console.log('handle register here');
}
</script>

<template>
    <main class="flex min-h-dvh items-center justify-center bg-background px-4 py-10 text-foreground">
        <Head title="Workspace invitation" />
        <div class="w-full max-w-2xl space-y-8 rounded-2xl border bg-card p-6 text-center shadow-xl sm:p-10">
            <div class="space-y-3">
                <p class="text-sm tracking-widest text-muted-foreground uppercase">Workspace Invitation</p>

                <h1
                    class="text-lg leading-relaxed font-medium text-balance text-muted-foreground md:text-xl lg:text-2xl"
                >
                    <span class="font-semibold text-foreground">
                        {{ invitation.inviter.name }}
                    </span>
                    <span class="mx-1">invited you to</span>
                    <span class="font-bold break-words text-primary">
                        {{ invitation.workspace.name }}
                    </span>
                </h1>
            </div>

            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Button
                    v-if="$page.props.auth.user"
                    class="w-full px-10 font-semibold sm:w-auto"
                    size="lg"
                    @click="acceptInvitation"
                >
                    Accept Invitation
                </Button>

                <template v-else>
                    <Button class="w-full sm:w-auto" size="lg" variant="outline" @click="handleLogin">Log in</Button>

                    <Button class="w-full sm:w-auto" size="lg" @click="handleRegister">Create Account</Button>
                </template>
            </div>
        </div>
    </main>
</template>

<style scoped></style>
