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
    workspaces: Workspace[],
}>();

onMounted(() => {
    router.visit(boardsRoutes.index().url, {
        only: ['workspaces'],
        preserveScroll: true,
        preserveState: true,
    })
})

</script>

<template>
    <Head title="Boards"/>

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="px-10">
            <div class="mt-10">
                <h3 class="font-semibold my-5">YOUR WORKSPACES</h3>
                <div class="">
                    <template v-for="workspace in props.workspaces" :key="workspace.id">
                        <div class="pb-10">
                            <div class="mb-4">
                                <h3 class="font-semibold">{{ workspace.name }}</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                <template v-for="board in workspace.boards" :key="board.id">
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
                                            v-bind="BoardController.store.form({workspace: workspace.id})"
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
        </main>
    </AppLayout>
</template>

<style scoped>
</style>
