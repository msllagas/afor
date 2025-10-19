<script lang="ts" setup>
import { inject } from 'vue'
import { Ellipsis } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

defineProps<{
    boardListId: string;
}>();

const emit = defineEmits<{
    archiveList: [boardId: string, boardListId: string]
}>()

const boardId = inject<string>('boardId', '');

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button size="sm" variant="ghost">
                <Ellipsis/>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56">
            <DropdownMenuLabel>List Actions</DropdownMenuLabel>
            <DropdownMenuSeparator/>
            <DropdownMenuGroup>
                <DropdownMenuItem>
                    <span>Add Card</span>
                </DropdownMenuItem>
                <DropdownMenuItem>
                    <span>Copy List</span>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator/>
            <DropdownMenuGroup>
                <DropdownMenuItem>
                    <span>Change list color</span>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator/>
            <DropdownMenuItem @click="emit('archiveList', boardId, boardListId)">
                <span>Archive this list</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped>

</style>
