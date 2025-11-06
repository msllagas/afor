<script lang="ts" setup>
import type {Invitation} from '@/types';
import {Button} from "@/components/ui/button";
import workspaceInvitationsRoutes from "@/routes/workspace-invitations";
import {router} from "@inertiajs/vue3";


const props = defineProps<{
    invitation: Invitation,
    savedToken?: boolean
}>();

function acceptInvitation() {
    router.post(workspaceInvitationsRoutes.accept({
        workspace: props.invitation.workspace.id,
        token: props.invitation.token
    }))
}

function handleLogin() {
    console.log('handle login here')
}
function handleRegister() {
    console.log('handle register here')
}
</script>

<template>
    <div>
        <h1><strong>{{ invitation.inviter.name }}</strong> is inviting you to <strong>{{
                invitation.workspace.name
            }}</strong></h1>

        <div class="flex items-center gap-4">
            <Button v-if="$page.props.auth.user" @click="acceptInvitation">
                Accept Invitation
            </Button>
            <template v-else>
                <Button class="cursor-pointer" variant="ghost" @click="handleLogin">
                    Login
                </Button>
                <Button class="cursor-pointer" variant="outline" @click="handleRegister">
                    Register
                </Button>
            </template>
        </div>
    </div>
</template>

<style scoped>

</style>
