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
import { Archive, ArchiveRestore, Ellipsis, LogOut, Plus, Users } from 'lucide-vue-next';

const props = defineProps<{
    /** Only the workspace owner can archive the board, so members don't get the option. */
    canArchive?: boolean;
    /** Board members can leave the board; the owner is on every board of their workspace. */
    canLeave?: boolean;
    isArchiving?: boolean;
    /** The board is archived, so every action is off. */
    isReadOnly?: boolean;
}>();

const emit = defineEmits<{
    addList: [];
    archiveBoard: [];
    leaveBoard: [];
    showArchivedItems: [];
    showMembers: [];
}>();

// "Add list" moves focus into the list composer, so the menu must not pull it back to its trigger.
let isMovingFocus = false;

function onAddList() {
    if (props.isReadOnly) {
        return;
    }

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
            <DropdownMenuItem :disabled="isReadOnly" class="cursor-pointer" @select="onAddList">
                <Plus />
                <span>Add list</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                :disabled="isReadOnly"
                class="cursor-pointer"
                @select="!isReadOnly && emit('showMembers')"
            >
                <Users />
                <span>Members</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                :disabled="isReadOnly"
                class="cursor-pointer"
                @select="!isReadOnly && emit('showArchivedItems')"
            >
                <ArchiveRestore />
                <span>Archived items</span>
            </DropdownMenuItem>
            <template v-if="canArchive">
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    :disabled="isReadOnly || isArchiving"
                    class="cursor-pointer"
                    @select="!isReadOnly && emit('archiveBoard')"
                >
                    <Archive />
                    <span>{{ isArchiving ? 'Archiving…' : 'Archive board' }}</span>
                </DropdownMenuItem>
            </template>
            <template v-if="canLeave">
                <DropdownMenuSeparator />
                <DropdownMenuItem class="cursor-pointer" variant="destructive" @select="emit('leaveBoard')">
                    <LogOut />
                    <span>Leave board</span>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
