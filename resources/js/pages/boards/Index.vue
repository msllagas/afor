<script lang="ts" setup>
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import BoardCard from '@/components/board/BoardCard.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import boardsRoutes from '@/routes/boards';
import type { BreadcrumbItem } from '@/types';
import type { Workspace } from '@/types/workspace/workspace';
import { Form, Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: boardsRoutes.index().url,
    },
];

const props = defineProps<{
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
}>();

onMounted(() => {
    router.visit(boardsRoutes.index().url, {
        only: ['ownedWorkspaces', 'sharedWorkspaces'],
        preserveScroll: true,
        preserveState: true,
    });
});
</script>

<template>
    <Head title="Boards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10">
            <div class="mt-10">
                <div class="my-5 space-y-1">
                    <h3 class="font-semibold text-gray-300">YOUR WORKSPACES</h3>
                    <h4 class="text-sm text-gray-500">Workspaces you own</h4>
                </div>
                <div class="">
                    <template v-for="ownedWorkspace in props.ownedWorkspaces" :key="ownedWorkspace.id">
                        <div class="pb-10">
                            <div class="mb-4">
                                <h3 class="font-semibold">
                                    {{ ownedWorkspace.name }}
                                </h3>
                            </div>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                <template v-for="board in ownedWorkspace.boards" :key="board.id">
                                    <BoardCard :board="board" />
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
                                            <CardContent
                                                class="relative flex h-32 items-center justify-center overflow-hidden"
                                            >
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
                                            v-bind="
                                                BoardController.store.form({
                                                    workspace: ownedWorkspace.id,
                                                })
                                            "
                                        >
                                            <div class="grid gap-2">
                                                <Label class="test-sm" for="name">Board Name</Label>
                                                <Input id="name" class="mt-1 block w-full" name="name" required />
                                                <InputError :message="errors.name" class="mt-2" />
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <Button :disabled="processing" data-test="add-board-button"
                                                    >Create
                                                </Button>
                                            </div>
                                        </Form>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>
                    </template>
                </div>
                <section v-if="props.sharedWorkspaces.length">
                    <div class="my-5 space-y-1">
                        <h3 class="font-semibold text-gray-300">SHARED WITH YOU</h3>
                        <h4 class="text-sm text-gray-500">Workspaces where you're a member</h4>
                    </div>
                    <div class="">
                        <template v-for="sharedWorkspace in props.sharedWorkspaces" :key="sharedWorkspace.id">
                            <div class="pb-10">
                                <div class="mb-4">
                                    <h3 class="font-semibold">
                                        {{ sharedWorkspace.name }}
                                    </h3>
                                </div>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                    <template v-for="board in sharedWorkspace.boards" :key="board.id">
                                        <BoardCard :board="board" />
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
                                                <CardContent
                                                    class="relative flex h-32 items-center justify-center overflow-hidden"
                                                >
                                                    <span
                                                        class="relative text-sm font-semibold text-white drop-shadow-md"
                                                    >
                                                        Create new board
                                                    </span>
                                                </CardContent>
                                            </Card>
                                        </PopoverTrigger>
                                        <PopoverContent class="rounded-xl bg-sidebar">
                                            <Form
                                                v-slot="{ errors, processing }"
                                                class="space-y-6"
                                                v-bind="
                                                    BoardController.store.form({
                                                        workspace: sharedWorkspace.id,
                                                    })
                                                "
                                            >
                                                <div class="grid gap-2">
                                                    <Label class="test-sm" for="name">Board Name</Label>
                                                    <Input id="name" class="mt-1 block w-full" name="name" required />
                                                    <InputError :message="errors.name" class="mt-2" />
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <Button :disabled="processing" data-test="add-board-button"
                                                        >Create
                                                    </Button>
                                                </div>
                                            </Form>
                                        </PopoverContent>
                                    </Popover>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
