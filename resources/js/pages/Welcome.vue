<script lang="ts" setup>
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from "@/components/ui/button";
</script>

<template>
    <Head title="Welcome">
        <link href="https://rsms.me/" rel="preconnect"/>
        <link href="https://rsms.me/inter/inter.css" rel="stylesheet"/>
    </Head>
    <div
        class=""
    >
        <header
            class="sticky h-16 top-0 z-50 w-full border-b bg-background/95 backdrop-blur dark:border-neutral-800/60"
        >
            <nav class="mx-auto h-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-full">
                    <div>
                        <h1 class="text-xl font-semibold text-pink-500">Afor</h1>
                    </div>
                    <div>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                            >
                                Log in
                            </Link>
                            <Link
                                :href="register()"
                                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Register
                            </Link>
                        </template>
                    </div>

                </div>
            </nav>
        </header>
        <main>
            <section class="">
                <div class="mx-auto max-w-6xl px-4 pt-12">
                    <div class="max-w-2xl mx-auto text-center">
                        <div>
                            <div>
                                <h1 class="text-4xl lg:text-6xl font-bold tracking-tight text-gray-900 dark:text-white">
                                    The Simple Way to Stay Organized
                                </h1>
                                <p class="lg:text-xl text-gray-500 dark:text-gray-400 mt-2 lg:mt-4">
                                    Plan better, work smarter, and accomplish more with Afor.
                                </p>
                            </div>
                            <div>
                                <Button class="mt-4 cursor-pointer font-bold" size="lg">Get Started</Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
