<script lang="ts" setup>
import BoardListController from '@/actions/App/Http/Controllers/BoardListController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Form } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { Plus, X } from 'lucide-vue-next';
import { nextTick, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    boardId: string;
    hasLists: boolean;
}>();

const emit = defineEmits<{
    added: [];
    opened: [];
    requestFailed: [message: string, status?: number];
}>();

const isAddingList = ref(!props.hasLists);
const listInput = useTemplateRef<HTMLInputElement>('list-input');
const listComposer = useTemplateRef<HTMLElement>('list-composer');

async function open() {
    isAddingList.value = true;
    await nextTick();
    listInput.value?.focus({ preventScroll: true });
    emit('opened');
}

function close() {
    isAddingList.value = false;
}

// Clicking anywhere else on the page puts the composer away, like pressing Escape.
onClickOutside(listComposer, close);

async function onAdded() {
    emit('added');
    await nextTick();
    listInput.value?.focus({ preventScroll: true });
}

function onNetworkError() {
    emit('requestFailed', 'Could not save. Check your connection and try again.');

    return false;
}

function onHttpException(response: { status: number }) {
    emit('requestFailed', 'Could not save. Check your connection and try again.', response.status);

    return false;
}

defineExpose({ open });
</script>

<template>
    <li class="w-[calc(100vw-3rem)] max-w-80 shrink-0 snap-center sm:w-72">
        <div
            v-if="isAddingList"
            ref="list-composer"
            class="rounded-2xl border border-primary/25 bg-card p-3 shadow-sm ring-1 ring-primary/10 dark:border-primary/30 dark:ring-primary/15"
        >
            <p v-if="!hasLists" class="mb-3 text-sm text-muted-foreground">
                Lists are the columns of your board, like
                <span class="font-medium text-foreground">To do</span>,
                <span class="font-medium text-foreground">Doing</span> and
                <span class="font-medium text-foreground">Done</span>.
            </p>
            <Form
                v-slot="{ errors, processing }"
                :options="{ preserveScroll: true, preserveState: true, only: ['board'] }"
                class="space-y-2"
                reset-on-success
                v-bind="BoardListController.store.form(boardId)"
                @http-exception="onHttpException"
                @network-error="onNetworkError"
                @success="onAdded"
            >
                <input
                    ref="list-input"
                    :aria-invalid="!!errors.name"
                    aria-label="List name"
                    autocomplete="off"
                    class="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive sm:text-sm dark:bg-input/30"
                    maxlength="255"
                    name="name"
                    placeholder="Enter list name"
                    required
                    @keydown.esc.prevent="close"
                />
                <InputError :message="errors.name" />
                <div class="flex items-center gap-1.5">
                    <Button :disabled="processing" class="cursor-pointer" size="sm" type="submit">
                        {{ processing ? 'Adding…' : 'Add list' }}
                    </Button>
                    <Button
                        aria-label="Stop adding lists"
                        class="size-8 cursor-pointer"
                        size="icon"
                        type="button"
                        variant="ghost"
                        @click="close"
                    >
                        <X />
                    </Button>
                </div>
            </Form>
        </div>
        <button
            v-else
            class="group flex h-12 w-full cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-primary/40 bg-card/80 px-3 text-sm font-medium text-foreground/80 shadow-xs backdrop-blur-sm transition-colors outline-none hover:border-primary/70 hover:bg-blush hover:text-blush-foreground focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:border-primary/50 dark:bg-card/60 dark:text-foreground/85 dark:hover:border-primary/80"
            type="button"
            @click="open"
        >
            <span
                aria-hidden="true"
                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary/15 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
            >
                <Plus class="size-4" />
            </span>
            {{ hasLists ? 'Add another list' : 'Add a list' }}
        </button>
    </li>
</template>
