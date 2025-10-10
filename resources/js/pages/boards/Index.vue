<script setup lang="ts">
import {onMounted} from "vue"
import {Form, Head, Link, router} from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import type {BreadcrumbItem} from "@/types"

import {Card, CardContent, CardFooter} from "@/components/ui/card"
import {Popover, PopoverTrigger, PopoverContent} from "@/components/ui/popover"
import {Button} from "@/components/ui/button"
import {Input} from "@/components/ui/input"
import {Label} from "@/components/ui/label"
import InputError from "@/components/InputError.vue"

import {cn} from "@/lib/utils"
import boards from "@/routes/boards"

import BoardController from "@/actions/App/Http/Controllers/BoardController"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: boards.index().url
    },
];

interface Boards {
    id: string,
    name: string,
}

const props = defineProps<{
    boards: Boards[]
}>();

onMounted(() => {
    router.visit(boards.index().url, {
        only: ['boards'],
        preserveScroll: true,
        preserveState: true,
    })
})

</script>

<template>
    <Head title="Boards"/>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-10">
            <div class="mb-4">
                <h3 class="uppercase font-semibold">your workspaces</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <template v-for="board in props.boards" :key="board.id">
                    <Link :href="boards.show(board.id).url">
                        <Card
                            :class="cn('w-full overflow-hidden rounded-2xl shadow-lg pt-0 gap-2 pb-2', $attrs.class ?? '')"
                        >
                            <CardContent
                                class="relative h-24 overflow-hidden bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 flex items-end p-4 group"
                            >
                                <!-- overlay -->
                                <div
                                    class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300"
                                ></div>
                            </CardContent>
                            <CardFooter class="m-0.5 px-6">
                                <span class="relative text-sm text-white font-semibold drop-shadow-md">
                                    {{ board.name }}
                                </span>
                            </CardFooter>
                        </Card>
                    </Link>
                </template>
                <Popover>
                    <PopoverTrigger as-child>
                        <Card
                            :class="cn('w-full overflow-hidden bg-sidebar hover:bg-sidebar-accent rounded-2xl shadow-lg pt-0 gap-2 pb-2', $attrs.class ?? '')"
                        >
                            <CardContent
                                class="relative h-32 overflow-hidden flex items-center justify-center"
                            >
                            <span class="relative text-sm text-white font-semibold drop-shadow-md">
                                    Create new board
                            </span>
                            </CardContent>
                        </Card>
                    </PopoverTrigger>
                    <PopoverContent class="rounded-xl bg-sidebar">
                        <Form
                            v-bind="BoardController.store.form()"
                            class="space-y-6"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-2">
                                <Label for="name" class="test-sm">Board Name</Label>
                                <Input
                                    id="name"
                                    class="mt-1 block w-full"
                                    name="name"
                                    required
                                />
                                <InputError class="mt-2" :message="errors.name"/>
                            </div>
                            <div class="flex items-center gap-4">
                                <Button
                                    :disabled="processing"
                                    data-test="add-board-button"
                                >Create
                                </Button
                                >
                            </div>
                        </Form>
                    </PopoverContent>
                </Popover>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
