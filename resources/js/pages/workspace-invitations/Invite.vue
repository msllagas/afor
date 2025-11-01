<script lang="ts" setup>
import type { Invitation } from '@/types';


defineProps<{
    invitation: Invitation
}>();

</script>

<template>
    <div>
        <h1> <strong>{{ invitation.inviter.name }}</strong> is inviting you to <strong>{{ invitation.workspace.name }}</strong></h1>
    </div>
</template>

<style scoped>

</style>
