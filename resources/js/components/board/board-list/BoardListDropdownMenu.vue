<script lang="ts" setup>
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
import { Archive, ArrowLeft, ArrowRight, Ellipsis, Palette, Plus } from 'lucide-vue-next';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    listName: string;
    color: string | null;
    colors: Array<string>;
    canMoveLeft: boolean;
    canMoveRight: boolean;
}>();

const emit = defineEmits<{
    addCard: [];
    move: [direction: -1 | 1];
    colorSelected: [color: string | null];
    archiveList: [];
}>();

// "Add card" moves focus into the composer, so the menu must not pull it back to its trigger.
let isMovingFocus = false;

function onAddCard() {
    isMovingFocus = true;
    emit('addCard');
}

function onCloseAutoFocus(event: Event) {
    if (isMovingFocus) {
        event.preventDefault();
        isMovingFocus = false;
    }
}

// An empty string stands for "no colour", since radio values can't be null.
function onColorChange(value: unknown) {
    const color = typeof value === 'string' && value !== '' ? value : null;

    if (color !== props.color) {
        emit('colorSelected', color);
    }
}

function colorLabel(color: string) {
    return color.charAt(0).toUpperCase() + color.slice(1);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                :aria-label="`Actions for list ${listName}`"
                class="size-8 shrink-0 cursor-pointer"
                size="icon"
                variant="ghost"
                v-bind="$attrs"
            >
                <Ellipsis />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56" @close-auto-focus="onCloseAutoFocus">
            <DropdownMenuLabel>List Actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem class="cursor-pointer" @select="onAddCard">
                <Plus />
                <span>Add card</span>
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="!canMoveLeft" class="cursor-pointer" @select="emit('move', -1)">
                <ArrowLeft />
                <span>Move list left</span>
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="!canMoveRight" class="cursor-pointer" @select="emit('move', 1)">
                <ArrowRight />
                <span>Move list right</span>
            </DropdownMenuItem>
            <DropdownMenuSub>
                <DropdownMenuSubTrigger class="cursor-pointer gap-2">
                    <Palette class="size-4 text-muted-foreground" />
                    <span>List colour</span>
                </DropdownMenuSubTrigger>
                <DropdownMenuPortal>
                    <DropdownMenuSubContent
                        class="max-h-(--reka-dropdown-menu-content-available-height) w-48 overflow-y-auto"
                    >
                        <DropdownMenuRadioGroup :model-value="color ?? ''" @update:model-value="onColorChange">
                            <DropdownMenuRadioItem class="cursor-pointer" value="">
                                <span
                                    aria-hidden="true"
                                    class="list-default size-4 rounded-full ring-1 ring-border [background:var(--list-bg)]"
                                />
                                Default
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem
                                v-for="option in colors"
                                :key="option"
                                :value="option"
                                class="cursor-pointer"
                            >
                                <span
                                    :class="`list-${option}`"
                                    aria-hidden="true"
                                    class="size-4 rounded-full ring-1 ring-black/10 [background:var(--list-bg)] dark:ring-white/15"
                                />
                                {{ colorLabel(option) }}
                            </DropdownMenuRadioItem>
                        </DropdownMenuRadioGroup>
                    </DropdownMenuSubContent>
                </DropdownMenuPortal>
            </DropdownMenuSub>
            <DropdownMenuSeparator />
            <DropdownMenuItem class="cursor-pointer" @select="emit('archiveList')">
                <Archive />
                <span>Archive list</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
