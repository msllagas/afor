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

const props = withDefaults(
    defineProps<{
        workspaceId: string;
        /** A grid tile, or a slim row for the compact list. */
        variant?: 'tile' | 'row';
    }>(),
    { variant: 'tile' },
);

const inputId = computed(() => `board-name-${props.workspaceId}`);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'group flex w-full cursor-pointer items-center justify-center border-2 border-dashed border-primary/40 bg-card/80 text-sm font-medium text-foreground/80 shadow-xs transition-colors duration-200 outline-none hover:border-primary/70 hover:bg-blush hover:text-blush-foreground focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 data-[state=open]:border-primary/70 data-[state=open]:bg-blush data-[state=open]:text-blush-foreground dark:border-primary/50 dark:bg-card/60 dark:text-foreground/85 dark:hover:border-primary/80 dark:data-[state=open]:border-primary/80',
                        variant === 'tile'
                            ? 'h-full min-h-36 flex-col gap-3 rounded-2xl'
                            : 'min-h-11 gap-3 rounded-xl px-3',
                        $attrs.class ?? '',
                    )
                "
            >
                <span
                    :class="variant === 'tile' ? 'size-10 rounded-xl' : 'size-7 rounded-lg'"
                    aria-hidden="true"
                    class="flex shrink-0 items-center justify-center bg-primary/15 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground group-data-[state=open]:bg-primary group-data-[state=open]:text-primary-foreground"
                >
                    <Plus :class="variant === 'tile' ? 'size-5' : 'size-4'" />
                </span>
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
