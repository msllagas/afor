<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItemType } from '@/types';

import 'vue-sonner/style.css';
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent class="overflow-x-hidden" variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster position="top-right" rich-colors />
    </AppShell>
</template>
