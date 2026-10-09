<script lang="ts" setup>
import BoardCard from '@/components/board/BoardCard.vue';
import BoardRow from '@/components/board/BoardRow.vue';
import BoardsToolbar from '@/components/board/BoardsToolbar.vue';
import WorkspaceBoardsSection from '@/components/board/WorkspaceBoardsSection.vue';
import WorkspaceIndex from '@/components/board/WorkspaceIndex.vue';
import { Button } from '@/components/ui/button';
import { useBoardStar } from '@/composables/useBoardStar';
import { useWorkspaceSync } from '@/composables/useWorkspaceSync';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/boards';
import type {
    Board,
    BoardsScope,
    BoardsView,
    BreadcrumbItem,
    Workspace,
    WorkspaceBoardsGroup,
    WorkspaceBoardsMatch,
} from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { refDebounced, useDebounceFn, useElementSize, useMediaQuery } from '@vueuse/core';
import { Star } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';

const VIEW_STORAGE_KEY = 'boards-index:view';
const OPEN_SECTIONS_STORAGE_KEY = 'boards-index:open-sections';
const SCOPES: BoardsScope[] = ['all', 'owned', 'shared', 'starred'];
// With only a few workspaces the page is short enough to scan, so a side index would just add noise.
const INDEX_MIN_WORKSPACES = 4;
// Below this many workspaces every section starts open.
const COLLAPSE_MIN_WORKSPACES = 6;
// Grid tiles are at least 15rem wide with a 1rem gap, matching the section's grid template.
const GRID_MIN_TILE = 240;
const GRID_GAP = 16;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boards',
        href: index().url,
    },
];

const props = defineProps<{
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
}>();

const page = usePage();
const { toggleStar } = useBoardStar();
const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');

const initialParams = new URLSearchParams(page.url.split('?')[1] ?? '');
const initialScope = initialParams.get('scope') as BoardsScope | null;

const query = ref(initialParams.get('q') ?? '');
const scope = ref<BoardsScope>(initialScope && SCOPES.includes(initialScope) ? initialScope : 'all');
const view = ref<BoardsView>('grid');
const storedOpenSections = ref<Record<string, boolean>>({});
// Sections the user closed during the current search; every match starts open.
const sectionsClosedInSearch = ref(new Set<string>());
const sectionsShowingAll = ref(new Set<string>());
const activeWorkspaceId = ref<string | null>(null);

const toolbar = useTemplateRef<HTMLElement>('toolbar');
const sectionsContainer = useTemplateRef<HTMLElement>('sections');
const { height: toolbarHeight } = useElementSize(toolbar, undefined, { box: 'border-box' });
const { width: sectionsWidth } = useElementSize(sectionsContainer);

const search = computed(() => query.value.trim().toLocaleLowerCase());
const isFiltering = computed(() => !!search.value || scope.value !== 'all');

const allWorkspaces = computed(() => [...props.ownedWorkspaces, ...props.sharedWorkspaces]);
const ownedIds = computed(() => new Set(props.ownedWorkspaces.map(({ id }) => id)));
const currentWorkspaceId = computed(() => page.props.currentWorkspaceId);

useWorkspaceSync({ workspaceId: null, boardProps: ['ownedWorkspaces', 'sharedWorkspaces'] });

const showsIndex = computed(() => allWorkspaces.value.length >= INDEX_MIN_WORKSPACES);

const columns = computed(() =>
    sectionsWidth.value ? Math.max(1, Math.floor((sectionsWidth.value + GRID_GAP) / (GRID_MIN_TILE + GRID_GAP))) : 3,
);

const starredBoards = computed(() =>
    allWorkspaces.value.flatMap(({ boards }) => boards.filter((board) => board.is_favorited)),
);
const showsStarredSection = computed(() => scope.value === 'all' && !search.value && starredBoards.value.length > 0);

const byName = (first: Workspace, second: Workspace) => first.name.localeCompare(second.name);
const includesSearch = (text: string) => text.toLocaleLowerCase().includes(search.value);

function matchWorkspace(workspace: Workspace): WorkspaceBoardsMatch | null {
    const boards =
        scope.value === 'starred' ? workspace.boards.filter((board) => board.is_favorited) : workspace.boards;

    // Without a search, an empty workspace still shows so a board can be added to it.
    if (!search.value || includesSearch(workspace.name)) {
        return boards.length || scope.value !== 'starred' ? { workspace, boards } : null;
    }

    const matchingBoards = boards.filter((board) => includesSearch(board.name));

    return matchingBoards.length ? { workspace, boards: matchingBoards } : null;
}

function matchAll(workspaces: Workspace[]) {
    return [...workspaces]
        .filter((workspace) => workspace.id !== currentWorkspaceId.value)
        .sort(byName)
        .map(matchWorkspace)
        .filter((match): match is WorkspaceBoardsMatch => match !== null);
}

const groups = computed<WorkspaceBoardsGroup[]>(() => {
    const currentWorkspace = allWorkspaces.value.find(({ id }) => id === currentWorkspaceId.value);
    const isCurrentOwned = !!currentWorkspace && ownedIds.value.has(currentWorkspace.id);
    const showsCurrent =
        !!currentWorkspace &&
        (scope.value === 'all' ||
            scope.value === 'starred' ||
            (scope.value === 'owned' && isCurrentOwned) ||
            (scope.value === 'shared' && !isCurrentOwned));
    const currentMatch = showsCurrent ? matchWorkspace(currentWorkspace) : null;

    return [
        { id: 'current' as const, title: 'Current workspace', matches: currentMatch ? [currentMatch] : [] },
        {
            id: 'owned' as const,
            title: 'Your workspaces',
            matches: scope.value === 'shared' ? [] : matchAll(props.ownedWorkspaces),
        },
        {
            id: 'shared' as const,
            title: 'Shared with you',
            matches: scope.value === 'owned' ? [] : matchAll(props.sharedWorkspaces),
        },
    ].filter((group) => group.matches.length);
});

const visibleMatches = computed(() => groups.value.flatMap((group) => group.matches));

const hasOpenSection = computed(() => visibleMatches.value.some(({ workspace }) => isSectionOpen(workspace.id)));

const emptyState = computed(() => {
    if (search.value) {
        return {
            title: `Nothing matches “${query.value.trim()}”`,
            description: 'Try another board or workspace name, or search everything.',
        };
    }

    return {
        starred: { title: 'No starred boards yet', description: 'Star a board to keep it close at hand.' },
        owned: {
            title: "You don't own a workspace",
            description: 'Workspaces other people share with you are under Shared.',
        },
        shared: {
            title: 'Nothing is shared with you yet',
            description: 'Ask a teammate for an invite link to join their workspace.',
        },
        all: { title: 'No boards yet', description: 'Open a workspace to add its first board.' },
    }[scope.value];
});

const resultAnnouncement = computed(() => {
    if (!isFiltering.value) {
        return '';
    }

    const boardCount = visibleMatches.value.reduce((total, { boards }) => total + boards.length, 0);

    return visibleMatches.value.length
        ? `${pluralize(boardCount, 'board')} in ${pluralize(visibleMatches.value.length, 'workspace')}`
        : 'No matches';
});
// Wait for typing to settle so screen readers aren't flooded with counts.
const announcement = refDebounced(resultAnnouncement, 600);

function pluralize(count: number, noun: string) {
    return `${count} ${count === 1 ? noun : `${noun}s`}`;
}

function isSectionOpen(workspaceId: string) {
    if (search.value) {
        return !sectionsClosedInSearch.value.has(workspaceId);
    }

    return (
        storedOpenSections.value[workspaceId] ??
        (workspaceId === currentWorkspaceId.value || allWorkspaces.value.length < COLLAPSE_MIN_WORKSPACES)
    );
}

function setSectionOpen(workspaceId: string, isOpen: boolean) {
    if (search.value) {
        if (isOpen) {
            sectionsClosedInSearch.value.delete(workspaceId);
        } else {
            sectionsClosedInSearch.value.add(workspaceId);
        }

        return;
    }

    storedOpenSections.value = { ...storedOpenSections.value, [workspaceId]: isOpen };
    writeStorage(OPEN_SECTIONS_STORAGE_KEY, storedOpenSections.value);
}

function toggleAllSections() {
    const isOpen = !hasOpenSection.value;

    if (search.value) {
        sectionsClosedInSearch.value = new Set(isOpen ? [] : visibleMatches.value.map(({ workspace }) => workspace.id));

        return;
    }

    storedOpenSections.value = {
        ...storedOpenSections.value,
        ...Object.fromEntries(visibleMatches.value.map(({ workspace }) => [workspace.id, isOpen])),
    };
    writeStorage(OPEN_SECTIONS_STORAGE_KEY, storedOpenSections.value);
}

function toggleShowAll(workspaceId: string) {
    if (!sectionsShowingAll.value.delete(workspaceId)) {
        sectionsShowingAll.value.add(workspaceId);
    }
}

async function jumpTo(workspaceId: string) {
    setSectionOpen(workspaceId, true);
    activeWorkspaceId.value = workspaceId;
    await nextTick();

    const section = document.querySelector<HTMLElement>(`[data-workspace-section="${CSS.escape(workspaceId)}"]`);

    section?.scrollIntoView({ behavior: prefersReducedMotion.value ? 'auto' : 'smooth', block: 'start' });
    section?.querySelector<HTMLElement>('[data-section-trigger]')?.focus({ preventScroll: true });
}

function resetFilters() {
    if (search.value) {
        query.value = '';
    } else {
        scope.value = 'all';
    }
}

function readStorage<T>(key: string, isValid: (value: unknown) => value is T): T | null {
    try {
        const value: unknown = JSON.parse(localStorage.getItem(key) ?? 'null');

        return isValid(value) ? value : null;
    } catch {
        return null;
    }
}

function writeStorage(key: string, value: unknown) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Storage can be blocked; the choice then only lasts for this visit.
    }
}

const isView = (value: unknown): value is BoardsView => value === 'grid' || value === 'list';
const isOpenSections = (value: unknown): value is Record<string, boolean> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

// Keep the search in the URL so the back button and shared links bring it back.
const syncUrl = useDebounceFn(() => {
    const [path, queryString = ''] = page.url.split('?');
    const params = new URLSearchParams(queryString);

    if (search.value) {
        params.set('q', query.value.trim());
    } else {
        params.delete('q');
    }

    if (scope.value === 'all') {
        params.delete('scope');
    } else {
        params.set('scope', scope.value);
    }

    const url = params.size ? `${path}?${params}` : path;

    if (url !== page.url) {
        router.replace({ url, preserveScroll: true, preserveState: true });
    }
}, 300);

watch([search, scope], syncUrl);

watch(search, () => {
    sectionsClosedInSearch.value = new Set();
});

watch(view, (value) => writeStorage(VIEW_STORAGE_KEY, value));

// Highlight the workspace being read in the side index.
let sectionObserver: IntersectionObserver | null = null;

function observeSections() {
    sectionObserver?.disconnect();

    const visibleIds = new Set<string>();

    sectionObserver = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                const id = (entry.target as HTMLElement).dataset.workspaceSection!;

                if (entry.isIntersecting) {
                    visibleIds.add(id);
                } else {
                    visibleIds.delete(id);
                }
            }

            const firstVisible = visibleMatches.value.find(({ workspace }) => visibleIds.has(workspace.id));

            if (firstVisible) {
                activeWorkspaceId.value = firstVisible.workspace.id;
            }
        },
        { rootMargin: '-20% 0px -60% 0px' },
    );

    sectionsContainer.value
        ?.querySelectorAll('[data-workspace-section]')
        .forEach((section) => sectionObserver!.observe(section));
}

watch(
    () => visibleMatches.value.map(({ workspace }) => workspace.id).join(),
    () => {
        activeWorkspaceId.value = visibleMatches.value[0]?.workspace.id ?? null;
        observeSections();
    },
    { flush: 'post' },
);

onMounted(() => {
    view.value = readStorage(VIEW_STORAGE_KEY, isView) ?? 'grid';
    storedOpenSections.value = readStorage(OPEN_SECTIONS_STORAGE_KEY, isOpenSections) ?? {};
    activeWorkspaceId.value = visibleMatches.value[0]?.workspace.id ?? null;
    observeSections();
});

onBeforeUnmount(() => sectionObserver?.disconnect());

function handleStarBoard(board: Board, isStarred: boolean) {
    board.is_favorited = isStarred;
    toggleStar(board, () => (board.is_favorited = !isStarred));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" content-class="overflow-x-clip">
        <Head title="Boards" />

        <div
            :style="{ '--boards-toolbar-height': `${toolbarHeight}px` }"
            class="mx-auto w-full max-w-7xl px-4 pt-8 pb-16 sm:px-6 sm:pt-10 lg:px-10"
        >
            <header>
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Boards</h1>
            </header>

            <div
                v-if="allWorkspaces.length"
                :class="{
                    'lg:grid lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8 xl:grid-cols-[16rem_minmax(0,1fr)]':
                        showsIndex,
                }"
                class="mt-4"
            >
                <aside v-if="showsIndex" class="hidden lg:block">
                    <div
                        class="sticky top-4 max-h-[calc(100dvh-2rem)] overflow-y-auto overscroll-contain pt-4 pr-1 pb-4"
                    >
                        <WorkspaceIndex :active-workspace-id="activeWorkspaceId" :groups="groups" @jump="jumpTo" />
                    </div>
                </aside>

                <div class="min-w-0">
                    <div
                        ref="toolbar"
                        class="sticky top-0 z-20 -mx-4 bg-background/90 px-4 py-3 backdrop-blur-md sm:-mx-6 sm:px-6 lg:-mx-2 lg:px-2"
                    >
                        <BoardsToolbar
                            v-model:query="query"
                            v-model:scope="scope"
                            v-model:view="view"
                            :active-workspace-id="activeWorkspaceId"
                            :can-jump="showsIndex"
                            :groups="groups"
                            :has-open-section="hasOpenSection"
                            @jump="jumpTo"
                            @toggle-all-sections="toggleAllSections"
                        />
                    </div>

                    <p aria-atomic="true" aria-live="polite" class="sr-only" role="status">{{ announcement }}</p>

                    <div ref="sections" class="mt-4">
                        <section v-if="showsStarredSection" aria-labelledby="starred-heading" class="mb-10">
                            <h2 id="starred-heading" class="mb-4 flex items-center gap-2 text-base font-semibold">
                                <Star aria-hidden="true" class="size-4 fill-primary text-primary" />
                                Starred
                            </h2>
                            <ul
                                v-if="view === 'grid'"
                                class="grid list-none grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] gap-4"
                            >
                                <li v-for="board in starredBoards" :key="board.id">
                                    <BoardCard :board="board" class="h-full" @star-board="handleStarBoard" />
                                </li>
                            </ul>
                            <ul v-else class="grid list-none gap-2">
                                <li v-for="board in starredBoards" :key="board.id">
                                    <BoardRow :board="board" @star-board="handleStarBoard" />
                                </li>
                            </ul>
                        </section>

                        <section
                            v-for="group in groups"
                            :key="group.id"
                            :aria-labelledby="`${group.id}-heading`"
                            class="not-first:mt-10"
                        >
                            <h2
                                :id="`${group.id}-heading`"
                                class="border-b pb-2 text-lg font-semibold tracking-tight sm:text-xl"
                            >
                                {{ group.title }}
                            </h2>

                            <div class="mt-4 space-y-5">
                                <WorkspaceBoardsSection
                                    v-for="{ workspace, boards } in group.matches"
                                    :key="workspace.id"
                                    :boards="boards"
                                    :can-add-board="!search && scope !== 'starred' && ownedIds.has(workspace.id)"
                                    :is-owned="ownedIds.has(workspace.id)"
                                    :columns="columns"
                                    :is-current="workspace.id === currentWorkspaceId"
                                    :is-showing-all="sectionsShowingAll.has(workspace.id)"
                                    :open="isSectionOpen(workspace.id)"
                                    :query="search"
                                    :view="view"
                                    :workspace="workspace"
                                    @star-board="handleStarBoard"
                                    @toggle-show-all="toggleShowAll(workspace.id)"
                                    @update:open="setSectionOpen(workspace.id, $event)"
                                />
                            </div>
                        </section>

                        <div v-if="!groups.length" class="rounded-2xl border-2 border-dashed px-6 py-14 text-center">
                            <h2 class="text-xl font-semibold">{{ emptyState.title }}</h2>
                            <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                                {{ emptyState.description }}
                            </p>
                            <Button
                                v-if="isFiltering"
                                class="mt-5 cursor-pointer"
                                variant="outline"
                                @click="resetFilters"
                            >
                                {{ search ? 'Clear search' : 'Show all boards' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="mt-12 rounded-2xl border-2 border-dashed px-6 py-14 text-center">
                <h2 class="text-xl font-semibold">No workspaces yet</h2>
                <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                    Boards live inside workspaces. Ask a teammate for an invite link to join theirs.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
