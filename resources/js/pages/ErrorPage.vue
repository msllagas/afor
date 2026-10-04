<script lang="ts" setup>
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as boardsIndex } from '@/routes/boards';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, LayoutGrid, Link2Off, RotateCw } from 'lucide-vue-next';
import { computed, onMounted, ref, useTemplateRef } from 'vue';

type ErrorStatus = 403 | 404 | 419 | 429 | 500 | 503;
type ErrorReason = 'invitation';

interface ErrorMessage {
    title: string;
    description: string;
    canRetry: boolean;
}

const props = defineProps<{
    status: ErrorStatus;
    /** Narrows a status down to what actually went wrong, when the server knows. */
    reason?: ErrorReason | null;
}>();

const REASON_MESSAGES: Record<ErrorReason, ErrorMessage> = {
    invitation: {
        title: 'This invite link doesn’t work',
        description:
            'It may have expired, been reset by the workspace owner, or never existed. Ask the owner for a new invite link.',
        canRetry: false,
    },
};

const MESSAGES: Record<ErrorStatus, ErrorMessage> = {
    403: {
        title: 'You can’t do that here',
        description: 'You don’t have permission for this. If you think you should, ask the workspace owner.',
        canRetry: false,
    },
    404: {
        title: 'We couldn’t find that page',
        description: 'It may have been moved or deleted, or you’re no longer a member of its workspace.',
        canRetry: false,
    },
    419: {
        title: 'Your session expired',
        description: 'For your security, the page timed out. Refresh it and try again.',
        canRetry: true,
    },
    429: {
        title: 'That’s a lot of requests',
        description: 'You’re going a little fast. Wait a moment, then try again.',
        canRetry: true,
    },
    500: {
        title: 'Something went wrong on our end',
        description: 'It’s not you, it’s us. Try again in a moment.',
        canRetry: true,
    },
    503: {
        title: 'We’ll be right back',
        description: 'Afor is down for a little maintenance. Check back in a few minutes.',
        canRetry: true,
    },
};

const page = usePage();
const heading = useTemplateRef<HTMLHeadingElement>('heading');
const canGoBack = ref(false);

const message = computed(
    () => (props.reason && REASON_MESSAGES[props.reason]) ?? MESSAGES[props.status] ?? MESSAGES[500],
);

// Errors outside a route (like an unknown URL) don't know who's signed in, so they fall back to the home page.
const isSignedIn = computed(() => !!page.props.auth?.user);

function goBack() {
    window.history.back();
}

function retry() {
    window.location.reload();
}

onMounted(() => {
    canGoBack.value = window.history.length > 1;
    // Visits swap the page in place, so announce the error by moving focus to it.
    heading.value?.focus({ preventScroll: true });
});
</script>

<template>
    <main
        class="relative isolate flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-background px-4 py-16 text-foreground"
    >
        <Head :title="message.title">
            <link
                head-key="font-fraunces"
                href="https://fonts.bunny.net/css?family=fraunces:500,600"
                rel="stylesheet"
            />
        </Head>

        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 [background-image:radial-gradient(color-mix(in_oklab,var(--primary)_14%,transparent)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_center,black_30%,transparent_75%)] [background-size:22px_22px]"
        />

        <Link
            :href="isSignedIn ? boardsIndex() : home()"
            aria-label="Afor home"
            class="mb-10 size-12 rounded-2xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 [&>svg]:size-full"
        >
            <AppLogoIcon />
        </Link>

        <div class="w-full max-w-md text-center">
            <span
                v-if="reason === 'invitation'"
                aria-hidden="true"
                class="mx-auto flex size-20 items-center justify-center rounded-3xl bg-blush text-blush-foreground ring-1 ring-primary/15"
            >
                <Link2Off class="size-9" />
            </span>
            <p
                v-else
                aria-hidden="true"
                class="bg-linear-to-br from-primary to-primary/50 bg-clip-text font-display text-7xl leading-none font-semibold tracking-tight text-transparent tabular-nums sm:text-8xl"
            >
                {{ status }}
            </p>
            <h1
                ref="heading"
                class="mt-5 font-display text-2xl font-semibold tracking-tight outline-none sm:text-3xl"
                tabindex="-1"
            >
                <span class="sr-only">Error {{ status }}: </span>{{ message.title }}
            </h1>
            <p class="mx-auto mt-3 max-w-sm text-sm text-muted-foreground sm:text-base">{{ message.description }}</p>

            <div class="mt-8 flex flex-col-reverse justify-center gap-2 sm:flex-row">
                <Button v-if="canGoBack" class="cursor-pointer" variant="ghost" @click="goBack">
                    <ArrowLeft aria-hidden="true" />
                    Go back
                </Button>
                <Button v-if="message.canRetry" class="cursor-pointer" variant="outline" @click="retry">
                    <RotateCw aria-hidden="true" />
                    {{ status === 419 ? 'Refresh the page' : 'Try again' }}
                </Button>
                <Button as-child class="cursor-pointer">
                    <Link :href="isSignedIn ? boardsIndex() : home()">
                        <LayoutGrid v-if="isSignedIn" aria-hidden="true" />
                        {{ isSignedIn ? 'Go to your boards' : 'Go to the home page' }}
                    </Link>
                </Button>
            </div>
        </div>
    </main>
</template>
