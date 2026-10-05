<script lang="ts" setup>
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { about, contact, dashboard, home, login, privacyPolicy, register, termsOfUse } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { ArrowLeftRight, Code, Github, Palette, Users } from 'lucide-vue-next';

type PreviewCard = {
    title: string;
    labels: string[];
    members: string[];
    isDragging?: boolean;
};

type PreviewList = {
    title: string;
    color: string;
    cards: PreviewCard[];
    hasDropTarget?: boolean;
    isHiddenOnMobile?: boolean;
};

const previewMembers = ['MA', 'JS', 'KR'];

const previewLists: PreviewList[] = [
    {
        title: 'To do',
        color: 'list-angel',
        isHiddenOnMobile: true,
        cards: [
            { title: 'Write the onboarding checklist', labels: ['list-blue'], members: ['JS'] },
            { title: 'Book the venue for Friday', labels: ['list-orange', 'list-angel'], members: ['MA', 'KR'] },
        ],
    },
    {
        title: 'Doing',
        color: 'list-purple',
        cards: [
            { title: 'Design the invite email', labels: ['list-angel'], members: ['MA'] },
            { title: 'Fix the login redirect', labels: ['list-red'], members: ['KR'] },
        ],
    },
    {
        title: 'Done',
        color: 'list-green',
        hasDropTarget: true,
        cards: [
            { title: 'Pick colors for each list', labels: ['list-angel'], members: ['MA', 'JS'], isDragging: true },
            { title: 'Set up the team workspace', labels: ['list-green'], members: ['JS'] },
        ],
    },
];

const listColors = ['list-angel', 'list-red', 'list-orange', 'list-yellow', 'list-green', 'list-blue', 'list-purple'];

const features = [
    {
        title: 'Share a workspace',
        description: 'Invite teammates or friends to a workspace and work on the same boards together.',
        icon: Users,
    },
    {
        title: 'Color your lists',
        description: 'Give each list its own color so you can tell your work apart at a glance.',
        icon: Palette,
        showsListColors: true,
    },
    {
        title: 'See changes live',
        description: 'Boards update for everyone as changes happen. Drag cards and lists to put them in order.',
        icon: ArrowLeftRight,
    },
];

const footerLinks = [
    { label: 'About', route: about() },
    { label: 'Privacy Policy', route: privacyPolicy() },
    { label: 'Terms of Use', route: termsOfUse() },
    { label: 'Contact', route: contact() },
];
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-background text-foreground">
        <SeoHead
            title="Afor - Kanban boards for the work you share"
            description="Afor is a kanban board for teams and friends. Put tasks on cards, sort them into colored lists, and see every change in real time."
        />
        <header class="sticky top-0 z-50 h-16 w-full border-b bg-background/90 backdrop-blur">
            <nav class="mx-auto flex h-full max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link
                    class="flex items-center gap-2.5 rounded-md text-2xl font-semibold text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    :href="home()"
                >
                    <span aria-hidden="true" class="size-8 shrink-0 [&>svg]:size-full">
                        <AppLogoIcon />
                    </span>
                    Afor
                </Link>
                <div class="flex items-center gap-2">
                    <Button v-if="$page.props.auth.user" as-child>
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>
                    <template v-else>
                        <Button as-child variant="ghost">
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="register()">Create account</Link>
                        </Button>
                    </template>
                </div>
            </nav>
        </header>

        <main class="flex-1">
            <section class="relative isolate overflow-hidden">
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[520px] bg-linear-to-b from-blush/70 to-transparent"
                />
                <div
                    class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-16 pb-20 sm:px-6 lg:grid-cols-12 lg:gap-10 lg:px-8 lg:pt-24 lg:pb-28"
                >
                    <div class="lg:col-span-5">
                        <h1 class="text-5xl leading-[1.05] font-semibold tracking-tight text-balance sm:text-6xl">
                            Plan together, one card at a time.
                        </h1>
                        <p class="mt-6 max-w-md text-lg leading-relaxed text-pretty text-muted-foreground">
                            Afor is a kanban board for the work you share. Put tasks on cards, sort them into lists, and
                            everyone in your workspace sees each move as it happens.
                        </p>
                        <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                            <Button v-if="$page.props.auth.user" as-child class="px-8" size="lg">
                                <Link :href="dashboard()">Go to your dashboard</Link>
                            </Button>
                            <template v-else>
                                <Button as-child class="px-8" size="lg">
                                    <Link :href="register()">Create your account</Link>
                                </Button>
                                <Button as-child class="px-8" size="lg" variant="outline">
                                    <Link :href="login()">Log in</Link>
                                </Button>
                            </template>
                        </div>
                    </div>

                    <div
                        aria-hidden="true"
                        class="duration-700 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-6 lg:col-span-7"
                    >
                        <div class="rounded-3xl border bg-card/80 p-3 shadow-xl shadow-primary/5 sm:p-4">
                            <div class="mb-3 flex items-center justify-between px-1">
                                <p class="font-semibold">Launch week</p>
                                <div class="flex -space-x-1">
                                    <span
                                        v-for="member in previewMembers"
                                        :key="member"
                                        class="flex size-7 items-center justify-center rounded-full bg-blush text-[10px] font-semibold text-blush-foreground ring-2 ring-card"
                                    >
                                        {{ member }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 items-start gap-3 sm:grid-cols-3">
                                <div
                                    v-for="list in previewLists"
                                    :key="list.title"
                                    :class="[list.color, { 'hidden sm:flex': list.isHiddenOnMobile }]"
                                    class="flex flex-col gap-2 rounded-2xl bg-(--list-bg) p-2"
                                >
                                    <p class="px-1.5 pt-1 text-sm font-semibold text-(--list-fg)">{{ list.title }}</p>
                                    <template v-for="card in list.cards" :key="card.title">
                                        <div
                                            :class="
                                                card.isDragging
                                                    ? 'relative z-10 -ml-5 rotate-3 shadow-xl ring-2 ring-primary/70'
                                                    : 'shadow-sm'
                                            "
                                            class="rounded-lg bg-card p-2.5 text-card-foreground"
                                        >
                                            <div class="mb-2 flex gap-1">
                                                <span
                                                    v-for="label in card.labels"
                                                    :key="label"
                                                    :class="label"
                                                    class="h-1.5 w-7 rounded-full bg-(--list-fg-muted)"
                                                />
                                            </div>
                                            <p class="text-[13px] leading-snug font-medium">{{ card.title }}</p>
                                            <div class="mt-3 flex justify-end -space-x-1">
                                                <span
                                                    v-for="member in card.members"
                                                    :key="member"
                                                    class="flex size-6 items-center justify-center rounded-full bg-blush text-[9px] font-semibold text-blush-foreground ring-2 ring-card"
                                                >
                                                    {{ member }}
                                                </span>
                                            </div>
                                        </div>
                                        <div
                                            v-if="card.isDragging && list.hasDropTarget"
                                            class="h-16 rounded-lg border-2 border-dashed border-(--list-fg-muted)/40"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t">
                <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
                    <h2 class="max-w-xl text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        Everything your team needs happens on the board.
                    </h2>
                    <div class="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-12">
                        <div v-for="feature in features" :key="feature.title" class="flex flex-col gap-3">
                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-blush text-blush-foreground"
                            >
                                <component :is="feature.icon" class="size-5" />
                            </div>
                            <h3 class="text-lg font-semibold">{{ feature.title }}</h3>
                            <p class="leading-relaxed text-muted-foreground">{{ feature.description }}</p>
                            <div v-if="feature.showsListColors" aria-hidden="true" class="flex gap-1.5 pt-1">
                                <span
                                    v-for="color in listColors"
                                    :key="color"
                                    :class="color"
                                    class="size-5 rounded-full bg-(--list-bg) ring-1 ring-(--list-fg-muted)/30"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 pb-20 sm:px-6 lg:px-8">
                <div
                    class="mx-auto flex max-w-6xl flex-col items-start gap-8 rounded-3xl bg-primary px-6 py-12 text-primary-foreground sm:px-12 md:flex-row md:items-center md:justify-between"
                >
                    <div class="max-w-lg">
                        <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                            <template v-if="$page.props.auth.user">Your boards are waiting</template>
                            <template v-else>Start your first board</template>
                        </h2>
                        <p class="mt-3 text-lg leading-relaxed text-primary-foreground/85">
                            Create an account, set up a workspace, and add your first list. It takes about a minute.
                        </p>
                    </div>
                    <Button
                        as-child
                        class="bg-primary-foreground px-8 text-primary hover:bg-primary-foreground/90"
                        size="lg"
                    >
                        <Link v-if="$page.props.auth.user" :href="dashboard()">Go to your dashboard</Link>
                        <Link v-else :href="register()">Create your account</Link>
                    </Button>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6 md:flex-row md:items-end md:justify-between lg:px-8"
            >
                <div>
                    <p class="text-xl font-semibold text-primary">Afor</p>
                    <p class="mt-1 text-sm text-muted-foreground">A kanban board for the work you share.</p>
                    <nav class="mt-5 flex flex-wrap gap-x-5 gap-y-2">
                        <Link
                            v-for="link in footerLinks"
                            :key="link.label"
                            :href="link.route"
                            class="rounded-sm text-sm text-muted-foreground transition-colors hover:text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
                </div>
                <div class="flex flex-col gap-4 md:items-end">
                    <div class="flex items-center gap-4">
                        <a
                            aria-label="GitHub profile"
                            class="rounded-sm text-muted-foreground transition-colors hover:text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            href="https://github.com/msllagas"
                            rel="noopener noreferrer"
                            target="_blank"
                        >
                            <Github class="size-5" />
                        </a>
                        <a
                            aria-label="Source code on GitHub"
                            class="rounded-sm text-muted-foreground transition-colors hover:text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            href="https://github.com/msllagas/afor"
                            rel="noopener noreferrer"
                            target="_blank"
                        >
                            <Code class="size-5" />
                        </a>
                    </div>
                    <p class="text-sm text-muted-foreground">© {{ new Date().getFullYear() }} Afor</p>
                </div>
            </div>
        </footer>
    </div>
</template>
