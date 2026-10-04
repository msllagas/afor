<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Archive, Ellipsis, Plus } from 'lucide-vue-next';

defineProps<{
    isArchiving?: boolean;
}>();

const emit = defineEmits<{
    addList: [];
    archiveBoard: [];
}>();

// "Add list" moves focus into the list composer, so the menu must not pull it back to its trigger.
let isMovingFocus = false;

function onAddList() {
    isMovingFocus = true;
    emit('addList');
}

function onCloseAutoFocus(event: Event) {
    if (isMovingFocus) {
        event.preventDefault();
        isMovingFocus = false;
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button aria-label="Board actions" class="cursor-pointer" size="icon" variant="ghost">
                <Ellipsis />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56" @close-auto-focus="onCloseAutoFocus">
            <DropdownMenuLabel>Board actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem class="cursor-pointer" @select="onAddList">
                <Plus />
                <span>Add list</span>
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="isArchiving" class="cursor-pointer" @select="emit('archiveBoard')">
                <Archive />
                <span>{{ isArchiving ? 'Archiving…' : 'Archive board' }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
