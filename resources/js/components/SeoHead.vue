<script setup lang="ts">
import { formatPageTitle } from '@/lib/utils';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        isHiddenFromSearch?: boolean;
    }>(),
    {
        isHiddenFromSearch: false,
    },
);

const page = usePage();

const fullTitle = computed(() => formatPageTitle(props.title, page.props.name));

const canonicalUrl = computed(() => {
    const url = new URL(page.url, page.props.appUrl);

    return `${url.origin}${url.pathname}`;
});

const imageUrl = computed(() => new URL('/apple-touch-icon.png', page.props.appUrl).href);
</script>

<template>
    <Head :title="title">
        <meta head-key="description" name="description" :content="description" />
        <meta v-if="isHiddenFromSearch" head-key="robots" name="robots" content="noindex, follow" />
        <link head-key="canonical" rel="canonical" :href="canonicalUrl" />

        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:site_name" property="og:site_name" :content="page.props.name" />
        <meta head-key="og:title" property="og:title" :content="fullTitle" />
        <meta head-key="og:description" property="og:description" :content="description" />
        <meta head-key="og:url" property="og:url" :content="canonicalUrl" />
        <meta head-key="og:image" property="og:image" :content="imageUrl" />

        <meta head-key="twitter:card" name="twitter:card" content="summary" />
        <meta head-key="twitter:title" name="twitter:title" :content="fullTitle" />
        <meta head-key="twitter:description" name="twitter:description" :content="description" />
    </Head>
</template>
