<script setup lang="ts">
import EchoChannel from '@/components/realtime/EchoChannel';
import { notifyWorkspace, onReconnect, startRealtime } from '@/lib/realtime';
import { reloadQuietly } from '@/lib/reloadQuietly';
import { usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed } from 'vue';

const REFRESH_DELAY_MS = 500;

const page = usePage();
const isRealtime = startRealtime();

const userId = computed(() => (isRealtime ? (page.props.auth?.user?.id ?? null) : null));

const workspaceIds = computed(() =>
    userId.value
        ? [...(page.props.ownedWorkspaces ?? []), ...(page.props.sharedWorkspaces ?? [])].map(({ id }) => id)
        : [],
);

const refreshWorkspaces = useDebounceFn(() => reloadQuietly(['ownedWorkspaces', 'sharedWorkspaces']), REFRESH_DELAY_MS);

function onWorkspaceHeard(workspaceId: string, event: string) {
    if (event === '.workspace.changed') {
        refreshWorkspaces();
        notifyWorkspace(workspaceId, 'workspace');
    } else {
        notifyWorkspace(workspaceId, 'board');
    }
}

if (isRealtime) {
    onReconnect(() => {
        if (userId.value) {
            refreshWorkspaces();
        }
    });
}
</script>

<template>
    <EchoChannel
        v-if="userId"
        :key="`user-${userId}`"
        :channel="`App.Models.User.${userId}`"
        :events="['.workspaces.changed']"
        @heard="refreshWorkspaces"
    />
    <EchoChannel
        v-for="workspaceId in workspaceIds"
        :key="workspaceId"
        :channel="`workspace.${workspaceId}`"
        :events="['.board.changed', '.workspace.changed']"
        @heard="(event: string) => onWorkspaceHeard(workspaceId, event)"
    />
</template>
