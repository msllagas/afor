<script lang="ts" setup>
import { onMounted } from "vue"
import { Form, Head, Link, router } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import type { BreadcrumbItem } from "@/types"
import { Card, CardContent, CardFooter } from "@/components/ui/card"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import InputError from "@/components/InputError.vue"
import { cn } from "@/lib/utils"
import boardsRoutes from "@/routes/boards"
import BoardController from "@/actions/App/Http/Controllers/BoardController"
import type { Workspace } from "@/types/workspace/workspace";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: boardsRoutes.index().url
    },
];

const props = defineProps<{
    ownedWorkspaces: Workspace[],
    sharedWorkspaces: Workspace[],
}>();

onMounted(() => {
    router.visit(boardsRoutes.index().url, {
        only: ['ownedWorkspaces', 'sharedWorkspaces'],
        preserveScroll: true,
        preserveState: true,
    })
})

</script>

<template>
    <Head title="Boards"/>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10">
                <div class="my-5 space-y-1">
                    <h3 class="text-gray-300 font-semibold">YOUR WORKSPACES</h3>
                    <h4 class="text-sm text-gray-500">Workspaces you own</h4>
                </div>
                <div class="">
                    <template v-for="ownedWorkspace in props.ownedWorkspaces" :key="ownedWorkspace.id">
                        <div class="pb-10">
                            <div class="mb-4">
                                <h3 class="font-semibold">{{ ownedWorkspace.name }}</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                <template v-for="board in ownedWorkspace.boards" :key="board.id">
                                    <Link :href="boardsRoutes.show(board.id).url">
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
                                            v-slot="{ errors, processing }"
                                            class="space-y-6"
                                            v-bind="BoardController.store.form({workspace: ownedWorkspace.id})"
                                        >
                                            <div class="grid gap-2">
                                                <Label class="test-sm" for="name">Board Name</Label>
                                                <Input
                                                    id="name"
                                                    class="mt-1 block w-full"
                                                    name="name"
                                                    required
                                                />
                                                <InputError :message="errors.name" class="mt-2"/>
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
                    </template>
                </div>
                <div class="my-5 space-y-1">
                    <h3 class="text-gray-300 font-semibold">SHARED WITH YOU</h3>
                    <h4 class="text-sm text-gray-500">Workspaces where you're a member</h4>
                </div>
                <div class="">
                    <template v-for="sharedWorkspace in props.sharedWorkspaces" :key="sharedWorkspace.id">
                        <div class="pb-10">
                            <div class="mb-4">
                                <h3 class="font-semibold">{{ sharedWorkspace.name }}</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                <template v-for="board in sharedWorkspace.boards" :key="board.id">
                                    <Link :href="boardsRoutes.show(board.id).url">
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
                                            v-slot="{ errors, processing }"
                                            class="space-y-6"
                                            v-bind="BoardController.store.form({workspace: sharedWorkspace.id})"
                                        >
                                            <div class="grid gap-2">
                                                <Label class="test-sm" for="name">Board Name</Label>
                                                <Input
                                                    id="name"
                                                    class="mt-1 block w-full"
                                                    name="name"
                                                    required
                                                />
                                                <InputError :message="errors.name" class="mt-2"/>
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
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
