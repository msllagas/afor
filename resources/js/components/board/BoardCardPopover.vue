<script lang="ts" setup>
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import InputError from '@/components/InputError.vue';
import UsageHint from '@/components/UsageHint.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { Usage } from '@/types';
import { Form } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        workspaceId: string;
        /** A grid tile, or a slim row for the compact list. */
        variant?: 'tile' | 'row';
        /** The workspace's boards against its limit; archived boards count too. */
        usage?: Usage | null;
    }>(),
    { variant: 'tile' },
);

const inputId = computed(() => `board-name-${props.workspaceId}`);
const isFull = computed(() => !!props.usage && props.usage.used >= props.usage.limit);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'group flex w-full cursor-pointer items-center justify-center border-2 border-dashed border-foreground/25 bg-card/80 text-sm font-medium text-foreground/80 shadow-xs transition-colors duration-200 outline-none hover:border-foreground/50 hover:bg-accent hover:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 data-[state=open]:border-foreground/50 data-[state=open]:bg-accent data-[state=open]:text-foreground dark:border-foreground/30 dark:bg-card/60 dark:text-foreground/85 dark:hover:border-foreground/55 dark:hover:bg-accent dark:hover:text-foreground dark:data-[state=open]:border-foreground/55 dark:data-[state=open]:bg-accent dark:data-[state=open]:text-foreground',
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
                    class="flex shrink-0 items-center justify-center bg-foreground/10 text-foreground/75 transition-colors group-hover:bg-foreground/15 group-hover:text-foreground group-data-[state=open]:bg-foreground/15 group-data-[state=open]:text-foreground"
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
                <UsageHint
                    v-if="usage"
                    :usage="usage"
                    full-message="This workspace has reached its board limit. Delete archived boards to make room."
                    noun="boards"
                />
                <Button :disabled="processing || isFull" class="w-full cursor-pointer" data-test="add-board-button"
                    >Create board</Button
                >
            </Form>
        </PopoverContent>
    </Popover>
</template>
