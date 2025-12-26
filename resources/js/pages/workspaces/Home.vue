<script lang="ts" setup>
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import boardsRoutes from '@/routes/boards';
import { default as workspaceRoutes, default as workspaces } from '@/routes/workspaces';
import type { BreadcrumbItem, Workspace } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{
    workspace: Workspace;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workspace',
        href: workspaceRoutes.home(props.workspace.id).url,
    },
    {
        title: 'Boards',
        href: workspaceRoutes.members(props.workspace.id).url,
    },
];

onMounted(() => {
    router.visit(workspaces.home(props.workspace.id).url, {
        only: ['workspace'],
        preserveScroll: true,
        preserveState: true,
    });
});
</script>

<template>
    <Head :title="workspace.name + ' - Boards'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10">
                <div class="space-y-2 pb-10">
                    <h1 class="text-lg font-medium">{{ workspace.name }}</h1>
                    <p class="text-sm text-gray-300">{{ workspace.description }}</p>
                </div>
                <hr class="my-5" />
                <div>
                    <div class="mb-5">
                        <h3 class="font-medium">All boards in this Workspace</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <template v-for="board in workspace.boards" :key="board.id">
                            <Link :href="boardsRoutes.show(board.id).url">
                                <Card
                                    :class="
                                        cn(
                                            'w-full gap-2 overflow-hidden rounded-2xl pt-0 pb-2 shadow-lg',
                                            $attrs.class ?? '',
                                        )
                                    "
                                >
                                    <CardContent
                                        class="group relative flex h-24 items-end overflow-hidden bg-gradient-to-r from-pink-500 via-fuchsia-500 to-rose-400 p-4"
                                    >
                                        <!-- overlay -->
                                        <div
                                            class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/20"
                                        ></div>
                                    </CardContent>
                                    <CardFooter class="m-0.5 px-6">
                                        <span class="relative text-sm font-semibold text-white drop-shadow-md">
                                            {{ board.name }}
                                        </span>
                                    </CardFooter>
                                </Card>
                            </Link>
                        </template>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Card
                                    :class="
                                        cn(
                                            'w-full gap-2 overflow-hidden rounded-2xl bg-sidebar pt-0 pb-2 shadow-lg hover:bg-sidebar-accent',
                                            $attrs.class ?? '',
                                        )
                                    "
                                >
                                    <CardContent class="relative flex h-32 items-center justify-center overflow-hidden">
                                        <span class="relative text-sm font-semibold text-white drop-shadow-md">
                                            Create new board
                                        </span>
                                    </CardContent>
                                </Card>
                            </PopoverTrigger>
                            <PopoverContent class="rounded-xl bg-sidebar">
                                <Form
                                    v-slot="{ errors, processing }"
                                    class="space-y-6"
                                    v-bind="BoardController.store.form({ workspace: workspace.id })"
                                >
                                    <div class="grid gap-2">
                                        <Label class="test-sm" for="name">Board Name</Label>
                                        <Input id="name" class="mt-1 block w-full" name="name" required />
                                        <InputError :message="errors.name" class="mt-2" />
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <Button :disabled="processing" data-test="add-board-button">Create </Button>
                                    </div>
                                </Form>
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
