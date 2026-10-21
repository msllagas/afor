<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Archive, ArchiveRestore, Ellipsis, LogOut, Palette, Plus, Users } from 'lucide-vue-next';

const props = defineProps<{
    /** Only the workspace owner can archive the board, so members don't get the option. */
    canArchive?: boolean;
    /** Board members can leave the board; the owner is on every board of their workspace. */
    canLeave?: boolean;
    /** The board card's cover color, as the workspace grid shows it. */
    color: string;
    colors: Array<string>;
    isArchiving?: boolean;
    /** The board is archived, so every action is off. */
    isReadOnly?: boolean;
}>();

const emit = defineEmits<{
    addList: [];
    archiveBoard: [];
    colorSelected: [color: string];
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

function onColorChange(value: unknown) {
    if (!props.isReadOnly && typeof value === 'string' && value !== props.color) {
        emit('colorSelected', value);
    }
}

function colorLabel(color: string) {
    return color.charAt(0).toUpperCase() + color.slice(1);
}

function onCloseAutoFocus(event: Event) {
    if (isMovingFocus) {
        event.preventDefault();
        isMovingFocus = false;
    }
}
</script>

<template>
    <!-- The tooltip wraps the whole menu: nesting both triggers makes them share one anchor and the menu won't open. -->
    <TooltipProvider :delay-duration="250">
        <Tooltip>
            <TooltipTrigger as-child>
                <div class="shrink-0">
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
                            <DropdownMenuSub>
                                <DropdownMenuSubTrigger :disabled="isReadOnly" class="cursor-pointer gap-2">
                                    <Palette class="size-4 text-muted-foreground" />
                                    <span>Cover color</span>
                                </DropdownMenuSubTrigger>
                                <DropdownMenuPortal>
                                    <DropdownMenuSubContent
                                        class="max-h-(--reka-dropdown-menu-content-available-height) w-48 overflow-y-auto"
                                    >
                                        <DropdownMenuRadioGroup
                                            :model-value="color"
                                            @update:model-value="onColorChange"
                                        >
                                            <DropdownMenuRadioItem
                                                v-for="option in colors"
                                                :key="option"
                                                :disabled="isReadOnly"
                                                :value="option"
                                                class="cursor-pointer"
                                            >
                                                <span
                                                    :class="`cover-${option}`"
                                                    aria-hidden="true"
                                                    class="size-4 rounded-full ring-1 ring-black/10 [background:var(--cover-bg)] dark:ring-white/15"
                                                />
                                                {{ colorLabel(option) }}
                                            </DropdownMenuRadioItem>
                                        </DropdownMenuRadioGroup>
                                    </DropdownMenuSubContent>
                                </DropdownMenuPortal>
                            </DropdownMenuSub>
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
                                <DropdownMenuItem
                                    class="cursor-pointer"
                                    variant="destructive"
                                    @select="emit('leaveBoard')"
                                >
                                    <LogOut />
                                    <span>Leave board</span>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </TooltipTrigger>
            <TooltipContent>Board Actions</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
