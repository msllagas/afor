<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import workspaceInvitationsRoutes from '@/routes/workspace-invitations';
import type { Invitation } from '@/types';
import { router } from '@inertiajs/vue3';

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
    <div class="space-y-8 py-12 text-center">
        <div class="mx-auto max-w-xl text-lg font-medium text-gray-700 md:text-xl lg:text-2xl dark:text-gray-300">
            <span class="font-semibold text-gray-900 dark:text-white">
                {{ invitation.inviter.name }}
            </span>
            <span class="mx-1">invited you to</span>
            <span class="font-bold text-primary">
                {{ invitation.workspace.name }}
            </span>
        </div>

        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Button v-if="$page.props.auth.user" class="px-8 font-semibold" size="lg" @click="acceptInvitation">
                Accept Invitation
            </Button>

            <template v-else>
                <Button class="px-8 font-medium" size="lg" variant="ghost" @click="handleLogin">Log in</Button>
                <Button class="px-8 font-semibold" size="lg" variant="outline" @click="handleRegister">
                    Create Account
                </Button>
            </template>
        </div>
    </div>
</template>

<style scoped></style>
