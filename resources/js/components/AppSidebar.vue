<script lang="ts" setup>
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, Workspace } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Kanban,
    LayoutGrid,
    SquareTerminal
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import boards from "@/routes/boards";
import NavWorkspace from "@/components/NavWorkspace.vue";
import workspacesRoutes from "@/routes/workspaces";
import type { PageProps as InertiaPageProps } from '@inertiajs/core'

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
    }
];

interface PageProps extends InertiaPageProps {
    ownedWorkspaces: Workspace[]
}

const page = usePage<PageProps>()


const ownedWorkspaces = page.props?.ownedWorkspaces.map(workspace => ({
    title: workspace.name,
    url: "#",
    icon: SquareTerminal,
    isActive: true,
    items: [
        {
            title: "Boards",
            url: workspacesRoutes.home(workspace.id).url,
        },
        {
            title: "Members",
            url: "#",
        },
        {
            title: "Settings",
            url: "#",
        },
    ]
}))
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
                        <Link :href="dashboard()">
                            <AppLogo/>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems"/>
            <NavWorkspace :items="ownedWorkspaces"/>
        </SidebarContent>

        <SidebarFooter>
            <NavUser/>
        </SidebarFooter>
    </Sidebar>
    <slot/>
</template>
