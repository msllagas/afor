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
import { inject } from 'vue';

defineProps<{
    boardListId: string;
    colors: Array<string>;
}>();

const emit = defineEmits<{
    archiveList: [boardId: string, boardListId: string];
    colorSelected: [color: string];
}>();

const boardId = inject<string>('boardId', '');
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button size="sm" variant="ghost">
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
                                class="h-8"
                                :style="{ backgroundColor: color }"
                                @click="emit('colorSelected', color)"
                            />
                        </DropdownMenuSubContent>
                    </DropdownMenuPortal>
                </DropdownMenuSub>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="emit('archiveList', boardId, boardListId)">
                <span>Archive this list</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped></style>
