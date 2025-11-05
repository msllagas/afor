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
</script>

<template>
    <div>
        <h1><strong>{{ invitation.inviter.name }}</strong> is inviting you to <strong>{{
                invitation.workspace.name
            }}</strong></h1>
        <Button @click="acceptInvitation">
            Accept Invitation
        </Button>

    </div>
</template>

<style scoped>

</style>
