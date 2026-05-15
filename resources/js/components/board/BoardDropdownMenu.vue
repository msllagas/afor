<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Archive, Ellipsis } from 'lucide-vue-next';

defineEmits<{
    archiveBoard: [];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button size="sm" variant="ghost" class="cursor-pointer">
                <Ellipsis />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56">
            <DropdownMenuLabel>Board Actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem @click="$emit('archiveBoard')" class="cursor-pointer">
                    <Archive />
                    <span>Archive board</span>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped></style>
