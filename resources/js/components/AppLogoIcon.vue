<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 396 396" width="256" height="256">
        <defs>
            <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#c0325a" />
                <stop offset="100%" stop-color="#8f1f42" />
            </linearGradient>
        </defs>

        <rect width="396" height="396" rx="88" fill="url(#bg)" />

        <path
            d="M 72 96 Q 72 64 104 64 L 292 64 Q 324 64 324 96 L 324 208 L 208 324 L 104 324 Q 72 324 72 292 Z"
            fill="none"
            stroke="rgba(255,255,255,0.18)"
            stroke-width="1"
        />

        <path
            d="M 72 96 Q 72 64 104 64 L 292 64 Q 324 64 324 96 L 324 208 L 208 324 L 104 324 Q 72 324 72 292 Z"
            fill="rgba(255,255,255,0.08)"
        />

        <path
            d="M 72 96 Q 72 64 104 64 L 292 64 Q 324 64 324 96 L 324 208 L 208 324 L 104 324 Q 72 324 72 292 Z"
            fill="none"
            stroke="white"
            stroke-width="20"
            stroke-linejoin="round"
            stroke-linecap="round"
        />

        <path
            d="M 208 324 L 208 208 L 324 208"
            fill="none"
            stroke="white"
            stroke-width="20"
            stroke-linejoin="round"
            stroke-linecap="round"
        />

        <rect x="114" y="118" width="66" height="30" rx="8" fill="white" />
        <rect x="206" y="118" width="66" height="30" rx="8" fill="white" />
        <rect x="114" y="172" width="66" height="30" rx="8" fill="white" />

        <circle cx="127" cy="133" r="4" fill="#c0325a" />
        <rect x="137" y="130" width="32" height="5" rx="2.5" fill="rgba(192,50,90,0.35)" />

        <circle cx="219" cy="133" r="4" fill="#c0325a" />
        <rect x="229" y="130" width="32" height="5" rx="2.5" fill="rgba(192,50,90,0.35)" />

        <circle cx="127" cy="187" r="4" fill="#c0325a" />
        <rect x="137" y="184" width="32" height="5" rx="2.5" fill="rgba(192,50,90,0.35)" />
    </svg>
</template>
