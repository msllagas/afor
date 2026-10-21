<script lang="ts" setup>
import type { Usage } from '@/types';
import { computed } from 'vue';

/** The count only shows once most of the allowance is taken. */
const NEAR_LIMIT_RATIO = 0.8;

const props = defineProps<{
    usage: Usage;
    /** Plural, such as "boards". */
    noun: string;
    /** Shown in place of the count once nothing is left. */
    fullMessage: string;
}>();

const isFull = computed(() => props.usage.used >= props.usage.limit);
const isNearLimit = computed(() => props.usage.used >= props.usage.limit * NEAR_LIMIT_RATIO);
</script>

<template>
    <p :class="isFull ? 'font-medium text-foreground' : 'text-muted-foreground'" class="text-xs" role="status">
        <template v-if="isFull">{{ fullMessage }}</template>
        <template v-else-if="isNearLimit">{{ usage.used }} of {{ usage.limit }} {{ noun }} used</template>
    </p>
</template>
