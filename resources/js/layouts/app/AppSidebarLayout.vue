<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import type { BreadcrumbItemType } from '@/types';

import 'vue-sonner/style.css';
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    /** Extra classes for the content area, e.g. to give a full-height page a fixed height. */
    contentClass?: string;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent :class="cn('overflow-x-hidden', contentClass)" variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster position="top-right" rich-colors />
    </AppShell>
</template>
