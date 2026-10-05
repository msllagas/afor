<script lang="ts" setup>
import SeoHead from '@/components/SeoHead.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-dvh items-center justify-center bg-background text-foreground">
        <SeoHead
            title="About"
            description="Learn about Afor, a kanban board for planning work together with your team."
            is-hidden-from-search
        />
        <div class="space-y-3 text-center">
            <p class="text-sm font-medium tracking-widest text-primary uppercase">Coming soon</p>
            <h1 class="text-3xl font-bold text-foreground">About Afor</h1>
            <p class="text-sm text-muted-foreground">This page is under construction. Check back later.</p>
            <Link
                :href="home()"
                class="mt-4 inline-block text-sm text-primary underline underline-offset-4 transition-opacity hover:opacity-75"
            >
                Back to home
            </Link>
        </div>
    </div>
</template>

<style scoped></style>
