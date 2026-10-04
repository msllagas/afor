<script lang="ts" setup>
import WorkspaceAvatar from '@/components/workspace/WorkspaceAvatar.vue';
import { cn } from '@/lib/utils';
import type { WorkspaceBoardsGroup } from '@/types';

defineProps<{
    groups: WorkspaceBoardsGroup[];
    activeWorkspaceId: string | null;
}>();

const emit = defineEmits<{
    jump: [workspaceId: string];
}>();
</script>

<template>
    <nav aria-label="Workspaces on this page" class="space-y-4">
        <div v-for="group in groups" :key="group.id">
            <p class="px-2 pb-1 text-xs font-medium text-muted-foreground">{{ group.title }}</p>
            <ul class="list-none space-y-0.5">
                <li v-for="{ workspace, boards } in group.matches" :key="workspace.id">
                    <button
                        :aria-current="workspace.id === activeWorkspaceId ? 'location' : undefined"
                        :class="
                            cn(
                                'flex w-full cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                workspace.id === activeWorkspaceId &&
                                    'bg-blush/70 font-medium text-blush-foreground hover:bg-blush',
                            )
                        "
                        type="button"
                        @click="emit('jump', workspace.id)"
                    >
                        <WorkspaceAvatar :workspace="workspace" class="size-5 rounded" />
                        <span :title="workspace.name" class="min-w-0 flex-1 truncate">{{ workspace.name }}</span>
                        <span class="shrink-0 text-xs text-muted-foreground tabular-nums">
                            {{ boards.length
                            }}<span class="sr-only"> {{ boards.length === 1 ? 'board' : 'boards' }}</span>
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </nav>
</template>
