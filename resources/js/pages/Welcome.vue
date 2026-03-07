<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { AlignStartHorizontal, Code, Github, Paintbrush, Users } from 'lucide-vue-next';

const features = [
    {
        title: 'Team Collaboration',
        description: 'Invite friends or teammates to your workspace and manage boards together in realtime.',
        icon: Users,
    },
    {
        title: 'Customizable Boards',
        description:
            'Personalize your board lists with color options to better organize and distinguish your workflow.',
        icon: Paintbrush,
    },
    {
        title: 'Real-Time & Flexible Layout',
        description:
            'See instant updates when boards are created and rearrange both cards and board lists with smooth drag and drop.',
        icon: AlignStartHorizontal,
    },
];
</script>

<template>
    <Head title="Welcome">
        <link href="https://rsms.me/" rel="preconnect" />
        <link href="https://rsms.me/inter/inter.css" rel="stylesheet" />
    </Head>
    <div class="">
        <header
            class="sticky top-0 z-50 h-16 w-full border-b bg-background/95 backdrop-blur dark:border-neutral-800/60"
        >
            <nav class="mx-auto h-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-full items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-primary">Afor</h1>
                    </div>
                    <div>
                        <Button v-if="$page.props.auth.user" as-child>
                            <Link
                                :href="dashboard()"
                                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        </Button>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                            >
                                Log in
                            </Link>
                            <Button as-child>
                                <Link
                                    :href="register()"
                                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Register
                                </Link>
                            </Button>
                        </template>
                    </div>
                </div>
            </nav>
        </header>
        <main>
            <section class="relative overflow-hidden">
                <div class="mx-auto max-w-6xl px-4 py-24">
                    <div class="mx-auto max-w-3xl text-center">
                        <h1
                            class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl dark:text-white"
                        >
                            The Simple Way to Stay Organized
                        </h1>
                        <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 sm:text-xl dark:text-gray-400">
                            Plan better, work smarter, and accomplish more with Afor.
                        </p>
                        <div
                            class="mx-auto mt-10 flex max-w-md flex-col justify-center gap-4 sm:max-w-none sm:flex-row"
                        >
                            <Button class="px-8 font-semibold" size="lg">Get Started</Button>
                            <Button class="px-8 font-semibold" size="lg" variant="outline"> Learn More</Button>
                        </div>
                    </div>
                </div>
            </section>
            <section class="relative overflow-hidden">
                <div class="mx-auto max-w-6xl px-4 pt-12 pb-24">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <template v-for="(feature, index) in features" :key="index">
                            <Card class="flex h-full flex-col gap-4 p-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <component :is="feature.icon" class="h-5 w-5" />
                                    </div>

                                    <CardTitle class="text-lg font-semibold">{{ feature.title }}</CardTitle>
                                </div>

                                <CardDescription class="text-base leading-relaxed text-muted-foreground">
                                    {{ feature.description }}
                                </CardDescription>
                            </Card>
                        </template>
                    </div>
                </div>
            </section>
            <section class="bg-gray-50 py-20 dark:bg-gray-900">
                <div class="mx-auto max-w-3xl px-4 text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">
                        Get Started with Afor
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                        Start organizing your work in minutes. Afor makes it simple and stress-free.
                    </p>
                    <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                        <Input
                            class="w-full max-w-md rounded-md border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                            placeholder="Enter your email"
                            type="email"
                        />
                        <Button class="px-8 font-semibold" size="lg">Get Started</Button>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        No credit card required. Start organizing your workflow today.
                    </p>
                </div>
            </section>
        </main>
        <footer class="border-t border-gray-200 dark:border-gray-800">
            <div class="mx-auto max-w-6xl px-4 py-10">
                <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
                    <div class="text-center sm:text-left">
                        <p class="text-base font-semibold text-gray-900 dark:text-white">Afor</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Plan better. Work smarter.</p>
                    </div>
                    <div class="flex items-center gap-5">
                        <a
                            aria-label="GitHub Profile"
                            class="text-gray-500 transition hover:text-primary"
                            href="https://github.com/msllagas"
                            rel="noopener noreferrer"
                            target="_blank"
                        >
                            <Github class="h-5 w-5" />
                        </a>
                        <a
                            aria-label="GitHub Repository"
                            class="text-gray-500 transition hover:text-primary dark:hover:text-primary"
                            href="https://github.com/msllagas/afor"
                            rel="noopener noreferrer"
                            target="_blank"
                        >
                            <Code class="h-5 w-5" />
                        </a>
                    </div>
                </div>
                <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    © {{ new Date().getFullYear() }} Afor
                </div>
            </div>
        </footer>
    </div>
</template>
