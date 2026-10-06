<script lang="ts" setup>
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import WorkspaceSwitcher from '@/components/workspace/WorkspaceSwitcher.vue';
import { urlIsActive } from '@/lib/utils';
import { dashboard } from '@/routes';
import boards from '@/routes/boards';
import workspacesRoutes from '@/routes/workspaces';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { House, Kanban, Settings, SquareKanban, Users } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import AppLogo from './AppLogo.vue';

const STARRED_LIMIT = 8;

const mainNavItems: NavItem[] = [
    {
        title: 'Boards',
        href: boards.index(),
        icon: Kanban,
    },
];

const page = usePage();
const { isMobile, openMobile, setOpenMobile } = useSidebar();

const isSwitcherOpen = ref(false);

// The URL's workspace, else the last one used; fall back to the first one the user can open
// (e.g. after the remembered workspace was deleted or the user was removed from it).
const currentWorkspace = computed(
    () =>
        [...page.props.ownedWorkspaces, ...page.props.sharedWorkspaces].find(
            (workspace) => workspace.id === page.props.currentWorkspaceId,
        ) ??
        page.props.ownedWorkspaces[0] ??
        page.props.sharedWorkspaces[0] ??
        null,
);

const workspaceNavItems = computed<NavItem[]>(() => {
    const workspace = currentWorkspace.value;

    if (!workspace) {
        return [];
    }

    const isOwner = page.props.ownedWorkspaces.some(({ id }) => id === workspace.id);

    return [
        { title: 'Home', href: workspacesRoutes.home(workspace.id), icon: House },
        { title: 'Members', href: workspacesRoutes.members(workspace.id), icon: Users },
        // Settings are owner-only, so members don't get a link to them.
        ...(isOwner ? [{ title: 'Settings', href: workspacesRoutes.settings(workspace.id), icon: Settings }] : []),
    ];
});

function isEditableTarget(target: EventTarget | null) {
    return (
        target instanceof HTMLElement &&
        (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    );
}

// Ctrl/⌘+K opens the workspace switcher. It lives here rather than in the switcher because on
// mobile the switcher only exists while the sidebar sheet is open.
useEventListener(window, 'keydown', async (event: KeyboardEvent) => {
    if (event.key.toLowerCase() !== 'k' || !(event.metaKey || event.ctrlKey) || event.altKey || event.shiftKey) {
        return;
    }

    // Leave the shortcut to text fields (e.g. the card editor) unless it's the switcher's own search box.
    if (isEditableTarget(event.target) && !isSwitcherOpen.value) {
        return;
    }

    event.preventDefault();

    if (isSwitcherOpen.value) {
        isSwitcherOpen.value = false;

        return;
    }

    if (isMobile.value && !openMobile.value) {
        setOpenMobile(true);
        await nextTick();
    }

    isSwitcherOpen.value = true;
});

// The switcher unmounts with the mobile sheet, so its open state has to reset with it.
watch(openMobile, (isSheetOpen) => {
    if (!isSheetOpen) {
        isSwitcherOpen.value = false;
    }
});

const visibleStarredBoards = computed(() => page.props.starredBoards.slice(0, STARRED_LIMIT));
const hiddenStarredCount = computed(() => Math.max(page.props.starredBoards.length - STARRED_LIMIT, 0));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <WorkspaceSwitcher
                v-model:open="isSwitcherOpen"
                :current-workspace="currentWorkspace"
                :owned-workspaces="page.props.ownedWorkspaces"
                :shared-workspaces="page.props.sharedWorkspaces"
            />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarGroup v-if="workspaceNavItems.length" class="px-2 py-0">
                <SidebarGroupLabel>This workspace</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in workspaceNavItems" :key="item.title">
                        <SidebarMenuButton :is-active="urlIsActive(item.href, page.url)" :tooltip="item.title" as-child>
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Starred boards have no distinct icons, so the section is hidden when the sidebar is collapsed. -->
            <SidebarGroup class="group-data-[collapsible=icon]:hidden">
                <SidebarGroupLabel>Starred</SidebarGroupLabel>
                <SidebarMenu v-if="visibleStarredBoards.length">
                    <SidebarMenuItem v-for="board in visibleStarredBoards" :key="board.id">
                        <SidebarMenuButton :is-active="page.url.startsWith(boards.show(board.id).url)" as-child>
                            <Link :href="boards.show(board.id)">
                                <SquareKanban class="text-muted-foreground" />
                                <span :title="board.name">{{ board.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-if="hiddenStarredCount">
                        <SidebarMenuButton as-child class="text-muted-foreground">
                            <Link :href="boards.index()">
                                <span>Show all starred ({{ page.props.starredBoards.length }})</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <p v-else class="px-2 text-xs text-muted-foreground">Star a board to keep it here.</p>
            </SidebarGroup>
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
