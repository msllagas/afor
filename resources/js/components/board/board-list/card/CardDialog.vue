<script lang="ts" setup>
import { Form, router } from '@inertiajs/vue3'
import { Textarea } from '@/components/ui/textarea'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import cardRoutes from '@/routes/board-lists/cards'
import type { Card } from '@/types'

defineProps<{
    selectedCard?: Card
    isFetching: boolean
}>();

const emit = defineEmits<{
    updateOpen: [value: boolean]
}>()

const isDialogOpen = defineModel<boolean>({ required: true })

function onSubmit(boardListId: string, cardId: string, event: Event) {
    const target = event.target
    if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) return

    const name = target.name
    const value = target.value

    router.patch(cardRoutes.update({
        board_list: boardListId,
        card: cardId
    }).url, {
        [name]: value,
    })
}

function autoResize(event: Event) {
    const target = event.target as HTMLTextAreaElement | null
    if (!target) return

    target.style.height = 'auto'
    target.style.height = `${target.scrollHeight}px`
}
</script>

<template>
    <Dialog v-model:open="isDialogOpen" @update:open="(value:boolean) => emit('updateOpen', value)">
        <DialogContent :class="{'[&>button:first-of-type]:hidden': isFetching}"
                       class="sm:max-w-[600px] max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto]"
        >
            <DialogHeader>
                <DialogTitle></DialogTitle>
                <DialogDescription>
                </DialogDescription>
            </DialogHeader>
            <div v-if="isFetching" class="[&>button:first-of-type]:hidden">
            </div>
            <div v-else-if="!isFetching && selectedCard" class="overflow-y-auto">
                <Form
                    class="space-y-6"
                >
                    <div class="grid gap-4 py-4 overflow-y-auto">
                            <div>
                                <h2 class="sr-only">{{ selectedCard.name }}</h2>
                                <Textarea
                                    id="name"
                                    :model-value="selectedCard.name"
                                    class="w-full text-2xl font-semibold leading-tight resize-none overflow-hidden border-none focus:ring-0 focus:outline-none p-0"
                                    name="name"
                                    rows="1"
                                    un-styled
                                    @keydown.enter.prevent="$event.target.blur()"
                                    @blur="onSubmit(selectedCard.board_list_id, selectedCard.id, $event)"
                                    @focus="autoResize"
                                    @input="autoResize"
                                />
                            </div>
                    </div>
                    <div class="space-y-4 py-2">
                        <h3 class="text-sm font-medium">Description</h3>
                        <Textarea
                            id="description"
                            :model-value="selectedCard.description"
                            class="w-full text-sm resize-none border border-gray-300 rounded-md p-2  focus:ring-1 "
                            name="description"
                            rows="1"
                            un-styled
                        />
                    </div>
                </Form>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>

</style>
