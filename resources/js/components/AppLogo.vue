<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        showName?: boolean;
    }>(),
    {
        showName: true,
    },
);

const page = usePage();

const appName = page.props.name;
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div v-if="showName" class="ml-1 grid flex-1 text-left text-sm">{{ appName }}</div>
</template>
