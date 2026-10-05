<script lang="ts" setup>
import WorkspaceJumpMenu from '@/components/board/WorkspaceJumpMenu.vue';
import { Button } from '@/components/ui/button';
import type { BoardsScope, BoardsView, WorkspaceBoardsGroup } from '@/types';
import { onKeyStroke } from '@vueuse/core';
import { ChevronsDownUp, ChevronsUpDown, LayoutGrid, List, Search, X } from 'lucide-vue-next';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { useTemplateRef } from 'vue';

const SCOPES: { value: BoardsScope; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'owned', label: 'Yours' },
    { value: 'shared', label: 'Shared' },
    { value: 'starred', label: 'Starred' },
];

defineProps<{
    groups: WorkspaceBoardsGroup[];
    activeWorkspaceId: string | null;
    hasOpenSection: boolean;
    /** Offer the jump menu below `lg`, where the side index is hidden. */
    canJump: boolean;
}>();

const emit = defineEmits<{
    jump: [workspaceId: string];
    toggleAllSections: [];
}>();

const query = defineModel<string>('query', { required: true });
const scope = defineModel<BoardsScope>('scope', { required: true });
const view = defineModel<BoardsView>('view', { required: true });

const searchInput = useTemplateRef<HTMLInputElement>('search-input');

const toggleClass =
    'inline-flex h-8 cursor-pointer items-center justify-center rounded-md px-2.5 text-sm font-medium text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 data-[state=on]:bg-background data-[state=on]:text-foreground data-[state=on]:shadow-xs dark:data-[state=on]:bg-foreground/15 dark:data-[state=on]:ring-1 dark:data-[state=on]:ring-white/10';

// "/" jumps to search from anywhere on the page, unless the user is already typing somewhere.
onKeyStroke('/', (event) => {
    const target = event.target as HTMLElement | null;

    if (
        event.metaKey ||
        event.ctrlKey ||
        event.altKey ||
        target?.closest('input, textarea, select, [contenteditable]')
    ) {
        return;
    }

    event.preventDefault();
    searchInput.value?.focus();
});

function onSearchEscape() {
    if (query.value) {
        query.value = '';
    } else {
        searchInput.value?.blur();
    }
}

function clearSearch() {
    query.value = '';
    searchInput.value?.focus();
}

function selectScope(value: unknown) {
    if (value) {
        scope.value = value as BoardsScope;
    }
}

function selectView(value: unknown) {
    if (value) {
        view.value = value as BoardsView;
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2" role="search">
        <div class="relative min-w-0 flex-[1_1_14rem]">
            <Search
                aria-hidden="true"
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <input
                ref="search-input"
                v-model="query"
                aria-keyshortcuts="/"
                aria-label="Search boards and workspaces"
                autocomplete="off"
                class="h-10 w-full rounded-lg border border-input bg-background pr-16 pl-9 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:text-sm dark:bg-input/30 [&::-webkit-search-cancel-button]:hidden"
                enterkeyhint="search"
                placeholder="Search boards and workspaces"
                type="search"
                @keydown.esc.prevent="onSearchEscape"
            />
            <button
                v-if="query"
                aria-label="Clear search"
                class="absolute top-1/2 right-1.5 flex size-7 -translate-y-1/2 cursor-pointer items-center justify-center rounded-md text-muted-foreground outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                type="button"
                @click="clearSearch"
            >
                <X aria-hidden="true" class="size-4" />
            </button>
            <kbd
                v-else
                aria-hidden="true"
                class="pointer-events-none absolute top-1/2 right-2.5 hidden -translate-y-1/2 rounded border bg-muted px-1.5 py-0.5 font-sans text-[11px] text-muted-foreground pointer-fine:inline"
            >
                /
            </kbd>
        </div>

        <div v-if="canJump" class="lg:hidden">
            <WorkspaceJumpMenu :active-workspace-id="activeWorkspaceId" :groups="groups" @jump="emit('jump', $event)" />
        </div>

        <div class="flex w-full items-center gap-2 sm:w-auto">
            <ToggleGroupRoot
                :model-value="scope"
                aria-label="Show boards from"
                class="grid flex-1 grid-cols-4 rounded-lg bg-muted p-1 sm:flex-none"
                type="single"
                @update:model-value="selectScope"
            >
                <ToggleGroupItem
                    v-for="option in SCOPES"
                    :key="option.value"
                    :class="toggleClass"
                    :value="option.value"
                >
                    {{ option.label }}
                </ToggleGroupItem>
            </ToggleGroupRoot>

            <Button
                :aria-label="hasOpenSection ? 'Collapse all workspaces' : 'Expand all workspaces'"
                :title="hasOpenSection ? 'Collapse all' : 'Expand all'"
                class="size-10 shrink-0 cursor-pointer"
                size="icon"
                variant="ghost"
                @click="emit('toggleAllSections')"
            >
                <ChevronsDownUp v-if="hasOpenSection" />
                <ChevronsUpDown v-else />
            </Button>

            <ToggleGroupRoot
                :model-value="view"
                aria-label="Layout"
                class="flex shrink-0 rounded-lg bg-muted p-1"
                type="single"
                @update:model-value="selectView"
            >
                <ToggleGroupItem :class="toggleClass" aria-label="Grid view" title="Grid view" value="grid">
                    <LayoutGrid aria-hidden="true" class="size-4" />
                </ToggleGroupItem>
                <ToggleGroupItem :class="toggleClass" aria-label="List view" title="List view" value="list">
                    <List aria-hidden="true" class="size-4" />
                </ToggleGroupItem>
            </ToggleGroupRoot>
        </div>
    </div>
</template>
