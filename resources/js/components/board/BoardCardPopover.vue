<script lang="ts" setup>
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Form } from '@inertiajs/vue3';

defineProps<{
    workspaceId: string;
}>();
</script>

<template>
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
                    <span class="relative text-sm font-semibold text-white drop-shadow-md"> Create new board </span>
                </CardContent>
            </Card>
        </PopoverTrigger>
        <PopoverContent class="rounded-xl bg-sidebar">
            <Form
                v-slot="{ errors, processing }"
                class="space-y-6"
                v-bind="
                    BoardController.store.form({
                        workspace: workspaceId,
                    })
                "
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
</template>

<style scoped></style>
