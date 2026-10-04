<script lang="ts" setup>
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Form } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
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
                        'flex h-full min-h-36 w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-primary/25 text-sm font-medium text-muted-foreground transition-colors duration-200 outline-none hover:border-primary/60 hover:bg-blush/50 hover:text-blush-foreground focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 data-[state=open]:border-primary/60 data-[state=open]:bg-blush/50 data-[state=open]:text-blush-foreground',
                        $attrs.class ?? '',
                    )
                "
            >
                <Plus class="size-5" />
                New board
            </button>
        </PopoverTrigger>
        <PopoverContent class="rounded-xl">
            <Form
                v-slot="{ errors, processing }"
                class="space-y-4"
                v-bind="
                    BoardController.store.form({
                        workspace: workspaceId,
                    })
                "
            >
                <div class="grid gap-2">
                    <Label :for="inputId">Board name</Label>
                    <Input :id="inputId" class="block w-full" name="name" placeholder="Launch week" required />
                    <InputError :message="errors.name" />
                </div>
                <Button :disabled="processing" class="w-full" data-test="add-board-button">Create board</Button>
            </Form>
        </PopoverContent>
    </Popover>
</template>
