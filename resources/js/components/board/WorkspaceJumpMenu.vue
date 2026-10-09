<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import WorkspaceAvatar from '@/components/workspace/WorkspaceAvatar.vue';
import type { WorkspaceBoardsGroup } from '@/types';
import { Check, ListTree, Search } from 'lucide-vue-next';
import { ListboxContent, ListboxFilter, ListboxGroup, ListboxGroupLabel, ListboxItem, ListboxRoot } from 'reka-ui';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    groups: WorkspaceBoardsGroup[];
    activeWorkspaceId: string | null;
}>();

const emit = defineEmits<{
    jump: [workspaceId: string];
}>();

const isOpen = ref(false);
const query = ref('');
const listbox = useTemplateRef<{ highlightFirstItem: () => void }>('listbox');

// Jumping moves focus to the workspace's heading, so the popover must not pull it back.
let isMovingFocus = false;

const matchingGroups = computed(() => {
    const search = query.value.trim().toLocaleLowerCase();

    return props.groups
        .map((group) => ({
            ...group,
            matches: group.matches.filter(({ workspace }) => workspace.name.toLocaleLowerCase().includes(search)),
        }))
        .filter((group) => group.matches.length);
});

function jumpTo(workspaceId: string) {
    isMovingFocus = true;
    isOpen.value = false;
    emit('jump', workspaceId);
}

function onCloseAutoFocus(event: Event) {
    if (isMovingFocus) {
        event.preventDefault();
        isMovingFocus = false;
    }
}

watch(query, async () => {
    await nextTick();
    listbox.value?.highlightFirstItem();
});

watch(isOpen, (open) => {
    if (!open) {
        query.value = '';
    }
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                :disabled="!groups.length"
                aria-label="Jump to a workspace"
                class="size-10 shrink-0 cursor-pointer data-[state=open]:bg-accent"
                size="icon"
                variant="outline"
            >
                <ListTree />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            align="end"
            class="w-72 max-w-[calc(100vw-2rem)] overflow-hidden p-0"
            @close-auto-focus="onCloseAutoFocus"
        >
            <ListboxRoot
                ref="listbox"
                :model-value="activeWorkspaceId ?? undefined"
                aria-label="Jump to a workspace"
                highlight-on-hover
            >
                <div class="flex items-center gap-2 border-b px-3">
                    <Search aria-hidden="true" class="size-4 shrink-0 text-muted-foreground" />
                    <ListboxFilter
                        v-model="query"
                        aria-label="Find a workspace"
                        auto-focus
                        class="h-10 min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground sm:text-sm"
                        placeholder="Jump to a workspace…"
                    />
                </div>

                <ListboxContent class="max-h-[min(20rem,55dvh)] overflow-y-auto overscroll-contain p-1">
                    <ListboxGroup v-for="group in matchingGroups" :key="group.id" class="not-first:mt-1">
                        <ListboxGroupLabel class="px-2 pt-1.5 pb-1 text-xs font-medium text-muted-foreground">
                            {{ group.title }}
                        </ListboxGroupLabel>
                        <ListboxItem
                            v-for="{ workspace } in group.matches"
                            :key="workspace.id"
                            :value="workspace.id"
                            class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-2 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                            @select.prevent="jumpTo(workspace.id)"
                        >
                            <WorkspaceAvatar :workspace="workspace" class="size-6 rounded" />
                            <span :title="workspace.name" class="min-w-0 flex-1 truncate">{{ workspace.name }}</span>
                            <Check
                                v-if="workspace.id === activeWorkspaceId"
                                aria-label="You're here"
                                class="size-4 shrink-0 text-primary"
                            />
                        </ListboxItem>
                    </ListboxGroup>

                    <p
                        v-if="!matchingGroups.length"
                        class="px-2 py-6 text-center text-sm text-muted-foreground"
                        role="status"
                    >
                        No workspaces match “{{ query.trim() }}”.
                    </p>
                </ListboxContent>
            </ListboxRoot>
        </PopoverContent>
    </Popover>
</template>
