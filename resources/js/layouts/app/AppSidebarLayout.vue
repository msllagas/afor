<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { useAppearance } from '@/composables/useAppearance';
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

// Toasts follow the chosen theme; on its own the toaster always renders light.
const { appearance } = useAppearance();
</script>

<template>
    <AppShell variant="sidebar">
        <a
            class="sr-only rounded-md bg-background text-sm font-medium text-foreground shadow-md ring-[3px] ring-ring/50 outline-none focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2"
            href="#main-content"
        >
            Skip to content
        </a>
        <AppSidebar />
        <AppContent
            id="main-content"
            :class="cn('overflow-x-hidden outline-none', contentClass)"
            tabindex="-1"
            variant="sidebar"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster :theme="appearance" position="top-right" rich-colors />
    </AppShell>
</template>
