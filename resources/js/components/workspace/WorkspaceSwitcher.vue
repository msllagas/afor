<script lang="ts" setup>
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import WorkspaceAvatar from '@/components/workspace/WorkspaceAvatar.vue';
import { home } from '@/routes/workspaces';
import type { Workspace } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next';
import { ListboxContent, ListboxFilter, ListboxGroup, ListboxGroupLabel, ListboxItem, ListboxRoot } from 'reka-ui';
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import { toast } from 'vue-sonner';

const RECENT_STORAGE_KEY = 'recent-workspaces';
const RECENT_LIMIT = 3;
// A "Recent" group only helps once the list is too long to scan at a glance.
const RECENT_MIN_WORKSPACES = 6;

const props = defineProps<{
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
    currentWorkspace: Workspace | null;
}>();

const page = usePage();
const { isMobile, state } = useSidebar();
const listbox = useTemplateRef<{ highlightFirstItem: () => void }>('listbox');

const isOpen = defineModel<boolean>('open', { default: false });
const query = ref('');
const recentIds = ref<string[]>([]);
const shortcutLabel = ref('Ctrl K');

const allWorkspaces = computed(() => [...props.ownedWorkspaces, ...props.sharedWorkspaces]);

const isOwnedByUser = (workspace: Workspace) => props.ownedWorkspaces.some(({ id }) => id === workspace.id);

const currentRole = computed(() =>
    props.currentWorkspace && isOwnedByUser(props.currentWorkspace) ? 'Your workspace' : 'Shared with you',
);

const recentWorkspaces = computed(() => {
    if (allWorkspaces.value.length < RECENT_MIN_WORKSPACES) {
        return [];
    }

    return recentIds.value
        .filter((id) => id !== props.currentWorkspace?.id)
        .map((id) => allWorkspaces.value.find((workspace) => workspace.id === id))
        .filter((workspace): workspace is Workspace => workspace !== undefined)
        .slice(0, RECENT_LIMIT);
});

const groups = computed(() => {
    const search = query.value.trim().toLocaleLowerCase();
    const matches = (workspace: Workspace) => workspace.name.toLocaleLowerCase().includes(search);

    return [
        { key: 'recent', label: 'Recent', workspaces: search ? [] : recentWorkspaces.value },
        { key: 'owned', label: 'Your workspaces', workspaces: props.ownedWorkspaces.filter(matches) },
        { key: 'shared', label: 'Shared with you', workspaces: props.sharedWorkspaces.filter(matches) },
    ].filter((group) => group.workspaces.length);
});

// Recent and the full lists can show the same workspace, so each option value carries its group.
const selectedValue = computed(() =>
    props.currentWorkspace
        ? `${isOwnedByUser(props.currentWorkspace) ? 'owned' : 'shared'}:${props.currentWorkspace.id}`
        : undefined,
);

function rememberRecent(workspaceId: string) {
    recentIds.value = [workspaceId, ...recentIds.value.filter((id) => id !== workspaceId)].slice(0, RECENT_LIMIT + 1);

    try {
        localStorage.setItem(RECENT_STORAGE_KEY, JSON.stringify(recentIds.value));
    } catch {
        // Storage can be blocked; recents then only last for this visit.
    }
}

function readRecentIds(): string[] {
    try {
        const stored: unknown = JSON.parse(localStorage.getItem(RECENT_STORAGE_KEY) ?? '[]');

        return Array.isArray(stored) ? stored.filter((id): id is string => typeof id === 'string') : [];
    } catch {
        return [];
    }
}

function openWorkspace(workspace: Workspace) {
    isOpen.value = false;

    const url = home(workspace.id).url;

    if (page.url === url) {
        return;
    }

    const showFailure = (status?: number) => {
        const message =
            status === 419
                ? 'Your session expired. Refresh the page, then try again.'
                : `Could not open ${workspace.name}.`;

        toast.error(message, {
            description: status === undefined ? 'Check your connection and try again.' : undefined,
            action: status === 419 ? undefined : { label: 'Try again', onClick: () => openWorkspace(workspace) },
        });
    };

    router.visit(url, {
        onHttpException: (response) => {
            showFailure(response.status);

            return false;
        },
        onNetworkError: () => {
            showFailure();

            return false;
        },
    });
}

watch(query, async () => {
    await nextTick();
    listbox.value?.highlightFirstItem();
});

watch(isOpen, (value) => {
    if (!value) {
        query.value = '';
    }
});

watch(
    () => props.currentWorkspace?.id,
    (id) => id && rememberRecent(id),
);

onMounted(() => {
    recentIds.value = readRecentIds();

    if (props.currentWorkspace) {
        rememberRecent(props.currentWorkspace.id);
    }

    if (/Mac|iPhone|iPad/.test(navigator.userAgent)) {
        shortcutLabel.value = '⌘K';
    }
});
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <Popover v-model:open="isOpen">
                <PopoverTrigger as-child>
                    <SidebarMenuButton
                        :aria-label="
                            currentWorkspace
                                ? `Switch workspace, current: ${currentWorkspace.name}`
                                : 'Switch workspace'
                        "
                        :tooltip="currentWorkspace?.name ?? 'Workspaces'"
                        aria-keyshortcuts="Control+K Meta+K"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        size="lg"
                    >
                        <WorkspaceAvatar v-if="currentWorkspace" :workspace="currentWorkspace" />
                        <span
                            v-else
                            aria-hidden="true"
                            class="size-8 shrink-0 rounded-md border-2 border-dashed border-sidebar-border"
                        />
                        <span class="grid min-w-0 flex-1 text-left leading-tight">
                            <span class="truncate font-medium">{{ currentWorkspace?.name ?? 'No workspace' }}</span>
                            <span class="truncate text-xs text-muted-foreground">
                                {{ currentWorkspace ? currentRole : 'Join one with an invite link' }}
                            </span>
                        </span>
                        <ChevronsUpDown class="ml-auto size-4 text-muted-foreground" aria-hidden="true" />
                    </SidebarMenuButton>
                </PopoverTrigger>

                <PopoverContent
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'right' : 'bottom'"
                    align="start"
                    class="w-(--reka-popover-trigger-width) max-w-[calc(100vw-2rem)] min-w-64 overflow-hidden p-0"
                >
                    <ListboxRoot ref="listbox" :model-value="selectedValue" aria-label="Workspaces" highlight-on-hover>
                        <div class="flex items-center gap-2 border-b px-3">
                            <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                            <ListboxFilter
                                v-model="query"
                                aria-label="Find a workspace"
                                auto-focus
                                class="h-10 min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                                placeholder="Find a workspace…"
                            />
                            <kbd
                                class="pointer-events-none hidden rounded border bg-muted px-1.5 py-0.5 font-sans text-[11px] text-muted-foreground sm:inline"
                                aria-hidden="true"
                            >
                                {{ shortcutLabel }}
                            </kbd>
                        </div>

                        <ListboxContent class="max-h-[min(20rem,55vh)] overflow-y-auto overscroll-contain p-1">
                            <ListboxGroup v-for="group in groups" :key="group.key" class="not-first:mt-1">
                                <ListboxGroupLabel class="px-2 pt-1.5 pb-1 text-xs font-medium text-muted-foreground">
                                    {{ group.label }}
                                </ListboxGroupLabel>
                                <ListboxItem
                                    v-for="workspace in group.workspaces"
                                    :key="`${group.key}:${workspace.id}`"
                                    :value="`${group.key}:${workspace.id}`"
                                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                    @select.prevent="openWorkspace(workspace)"
                                >
                                    <WorkspaceAvatar :workspace="workspace" class="size-6 rounded" />
                                    <span class="min-w-0 flex-1 truncate" :title="workspace.name">
                                        {{ workspace.name }}
                                    </span>
                                    <Check
                                        v-if="workspace.id === currentWorkspace?.id"
                                        class="size-4 shrink-0 text-primary"
                                        aria-hidden="true"
                                    />
                                </ListboxItem>
                            </ListboxGroup>

                            <p
                                v-if="!groups.length"
                                class="px-2 py-6 text-center text-sm text-muted-foreground"
                                role="status"
                            >
                                <template v-if="query.trim()">No workspaces match “{{ query.trim() }}”.</template>
                                <template v-else
                                    >You're not in any workspace yet. Ask a teammate for an invite link.</template
                                >
                            </p>
                        </ListboxContent>
                    </ListboxRoot>
                </PopoverContent>
            </Popover>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
