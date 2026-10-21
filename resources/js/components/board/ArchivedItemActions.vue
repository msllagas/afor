<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ArchiveRestore, Trash2 } from 'lucide-vue-next';

defineProps<{
    itemId: string;
    /** Names the item for screen readers, such as "list Doing". */
    itemLabel: string;
    /** What goes with the item, shown while confirming. */
    contents?: string;
    /** Such as "Delete list". */
    deleteLabel: string;
    /** Only the workspace owner deletes archived items, so everyone else gets just the restore button. */
    canDelete: boolean;
    isConfirmingDelete: boolean;
}>();

const emit = defineEmits<{
    restore: [];
    askToDelete: [];
    cancelDelete: [];
    delete: [];
}>();
</script>

<template>
    <!-- Lays its children out in the row itself, so the confirmation can take a line of its own. -->
    <div class="contents">
        <div
            v-if="isConfirmingDelete"
            :aria-label="`Confirm deleting ${itemLabel}`"
            class="flex basis-full flex-col gap-3 border-t border-destructive/20 pt-3 sm:flex-row sm:items-center sm:justify-between"
            role="group"
        >
            <p class="text-xs text-foreground">
                <span class="font-semibold">Delete permanently?</span>
                {{ contents }}
            </p>
            <div class="flex shrink-0 justify-end gap-2">
                <Button
                    :id="`cancel-delete-${itemId}`"
                    class="cursor-pointer"
                    size="sm"
                    variant="ghost"
                    @click="emit('cancelDelete')"
                >
                    Cancel
                </Button>
                <Button class="cursor-pointer" size="sm" variant="destructive" @click="emit('delete')">
                    <Trash2 aria-hidden="true" />
                    {{ deleteLabel }}
                </Button>
            </div>
        </div>
        <div v-else class="flex shrink-0 items-center gap-1.5">
            <Button :data-restore="itemId" class="cursor-pointer" size="sm" variant="outline" @click="emit('restore')">
                <ArchiveRestore aria-hidden="true" />
                Restore<span class="sr-only"> {{ itemLabel }}</span>
            </Button>
            <TooltipProvider v-if="canDelete">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            :id="`delete-${itemId}`"
                            :aria-label="`Delete ${itemLabel} permanently`"
                            class="size-8 cursor-pointer text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                            size="icon"
                            variant="ghost"
                            @click="emit('askToDelete')"
                        >
                            <Trash2 aria-hidden="true" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Delete permanently</TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
    </div>
</template>
