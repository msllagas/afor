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
import { Kanban, LayoutGrid, SquareTerminal } from 'lucide-vue-next';
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
    workspaces: Workspace[];
}

const page = usePage<PageProps>();

const workspaces = page.props?.workspaces.map((workspace) => ({
    title: workspace.name,
    url: '#',
    icon: SquareTerminal,
    isActive: true,
    items: [
        {
            title: 'Home',
            url: workspacesRoutes.home(workspace.id).url,
        },
        {
            title: 'Members',
            url: workspacesRoutes.members(workspace.id).url,
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
            <NavWorkspace :items="workspaces" />
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
