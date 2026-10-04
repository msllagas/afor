<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    text: string;
    query?: string;
}>();

const segments = computed(() => {
    const search = props.query?.trim().toLocaleLowerCase() ?? '';
    const haystack = props.text.toLocaleLowerCase();

    // Lowercasing can change the length of some characters; skip highlighting rather than slice wrongly.
    if (!search || haystack.length !== props.text.length) {
        return [{ text: props.text, isMatch: false }];
    }

    const parts: { text: string; isMatch: boolean }[] = [];
    let start = 0;
    let index = haystack.indexOf(search);

    while (index !== -1) {
        if (index > start) {
            parts.push({ text: props.text.slice(start, index), isMatch: false });
        }

        parts.push({ text: props.text.slice(index, index + search.length), isMatch: true });
        start = index + search.length;
        index = haystack.indexOf(search, start);
    }

    if (start < props.text.length) {
        parts.push({ text: props.text.slice(start), isMatch: false });
    }

    return parts;
});
</script>

<template>
    <span>
        <template v-for="(segment, index) in segments" :key="index">
            <mark v-if="segment.isMatch" class="rounded-xs bg-primary/20 text-inherit dark:bg-primary/30">{{
                segment.text
            }}</mark>
            <template v-else>{{ segment.text }}</template>
        </template>
    </span>
</template>
