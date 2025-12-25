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
    <div class="space-y-6 py-10 text-center">
        <div class="text-gray-900 md:text-xl lg:text-2xl dark:text-white">
            <strong>{{ invitation.inviter.name }}</strong> is inviting you to
            <strong>{{ invitation.workspace.name }}</strong>
        </div>
        <div>
            <Button v-if="$page.props.auth.user" class="cursor-pointer" @click="acceptInvitation">
                Accept Invitation
            </Button>
            <template v-else>
                <Button class="cursor-pointer" variant="ghost" @click="handleLogin"> Login </Button>
                <Button class="cursor-pointer" variant="outline" @click="handleRegister"> Register </Button>
            </template>
        </div>
    </div>
</template>

<style scoped></style>
