<script lang="ts" setup>
import NavMain from '@/components/NavMain.vue';
import NavWorkspace from '@/components/NavWorkspace.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import boards from '@/routes/boards';
import workspacesRoutes from '@/routes/workspaces';
import { type NavItem, Workspace } from '@/types';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Link, usePage } from '@inertiajs/vue3';
import { Kanban, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Boards',
        href: boards.index(),
        icon: Kanban,
    },
];

interface PageProps extends InertiaPageProps {
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
}

const page = usePage<PageProps>();

const ownedWorkspaces = page.props?.ownedWorkspaces.map((workspace) => ({
    title: workspace.name,
    logo: workspace.logo,
    url: '#',
    isActive: page.url.startsWith(`/workspaces/${workspace.id}`),
    items: [
        {
            title: 'Home',
            url: workspacesRoutes.home(workspace.id).url,
        },
        {
            title: 'Members',
            url: workspacesRoutes.members(workspace.id).url,
        },
        {
            title: 'Settings',
            url: workspacesRoutes.settings(workspace.id).url,
        },
    ],
}));

const sharedWorkspaces = page.props?.sharedWorkspaces.map((workspace) => ({
    title: workspace.name,
    logo: workspace.logo,
    url: '#',
    isActive: page.url.startsWith(`/workspaces/${workspace.id}`),
    items: [
        {
            title: 'Home',
            url: workspacesRoutes.home(workspace.id).url,
        },
        {
            title: 'Members',
            url: workspacesRoutes.members(workspace.id).url,
        },
        {
            title: 'Settings',
            url: workspacesRoutes.settings(workspace.id).url,
        },
    ],
}));
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
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavWorkspace :items="ownedWorkspaces" title="My Workspaces" />
            <NavWorkspace :items="sharedWorkspaces" title="Shared With Me" />
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
