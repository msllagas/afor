<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import { Ellipsis } from 'lucide-vue-next';

defineProps<{
    boardListId: string;
    colors: Array<string>;
}>();

const emit = defineEmits<{
    archiveList: [];
    colorSelected: [color: string | null];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button size="sm" variant="ghost" v-bind="$attrs">
                <Ellipsis />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56">
            <DropdownMenuLabel>List Actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem>
                    <span>Add Card</span>
                </DropdownMenuItem>
                <DropdownMenuItem>
                    <span>Copy List</span>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuSub>
                    <DropdownMenuSubTrigger>
                        <span>Change list color</span>
                    </DropdownMenuSubTrigger>
                    <DropdownMenuPortal>
                        <DropdownMenuSubContent class="grid grid-cols-2 gap-1 space-y-1">
                            <DropdownMenuItem
                                v-for="color in colors"
                                :key="color"
                                class="h-8 hover:opacity-90"
                                :class="`list-${color.toLowerCase()}`"
                                :style="{ background: 'var(--list-bg)' }"
                                @click="emit('colorSelected', color)"
                            />
                            <DropdownMenuItem
                                class="col-span-2 block text-center"
                                @click="emit('colorSelected', 'neutral')"
                            >
                                Reset
                            </DropdownMenuItem>
                        </DropdownMenuSubContent>
                    </DropdownMenuPortal>
                </DropdownMenuSub>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="emit('archiveList')">
                <span>Archive this list</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped></style>
