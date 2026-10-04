<script lang="ts" setup>
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    workspaceId: string;
}>();

const inputId = computed(() => `board-name-${props.workspaceId}`);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'flex w-full cursor-pointer flex-col overflow-hidden rounded-2xl border bg-sidebar pt-0 pb-2 text-card-foreground shadow-lg outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        $attrs.class ?? '',
                    )
                "
            >
                <span class="relative flex h-32 items-center justify-center overflow-hidden px-6">Create new board</span>
            </button>
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
                    <Label class="test-sm" :for="inputId">Board Name</Label>
                    <Input :id="inputId" class="mt-1 block w-full" name="name" required />
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
