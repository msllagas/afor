<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import type { Workspace } from '@/types';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    workspace: Pick<Workspace, 'name' | 'logo'>;
    class?: HTMLAttributes['class'];
}>();

const { getInitials } = useInitials();
</script>

<template>
    <Avatar :class="cn('size-8 shrink-0 rounded-md ring-1 ring-border/60', props.class)">
        <AvatarImage :src="workspace.logo ?? ''" alt="" class="object-cover" />
        <AvatarFallback class="rounded-[inherit] bg-blush text-xs font-semibold text-blush-foreground">
            {{ getInitials(workspace.name) }}
        </AvatarFallback>
    </Avatar>
</template>
