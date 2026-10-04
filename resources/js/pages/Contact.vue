<script lang="ts" setup>
import SeoHead from '@/components/SeoHead.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-background text-foreground">
        <SeoHead
            title="Contact"
            description="Questions or feedback about Afor? Email the Afor team at afor@mandyllagas.com."
        />
        <div class="space-y-3 text-center">
            <p class="text-sm font-medium tracking-widest text-primary uppercase">Contact</p>
            <h1 class="text-3xl font-bold text-foreground">Get in Touch</h1>
            <p class="text-sm text-muted-foreground">Have questions or feedback? We'd love to hear from you.</p>

            <a
                href="mailto:afor@mandyllagas.com"
                class="mt-4 inline-block text-sm text-primary underline underline-offset-4 transition-opacity hover:opacity-75"
            >
                afor@mandyllagas.com
            </a>
            <Link
                :href="home()"
                class="block text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-primary hover:underline"
            >
                Back to home
            </Link>
        </div>
    </div>
</template>

<style scoped></style>
