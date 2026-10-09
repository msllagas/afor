import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
export * from './workspace-invitation/workspace-invitation';
export * from './workspace/workspace';

import type { StarredBoard, Workspace } from './workspace/workspace';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    appUrl: string;
    auth: Auth;
    sidebarOpen: boolean;
    ownedWorkspaces: Workspace[];
    sharedWorkspaces: Workspace[];
    currentWorkspaceId: string | null;
    starredBoards: StarredBoard[];
};

export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

/** Other people in a workspace; their email addresses stay private. */
export type WorkspaceMember = Pick<User, 'id' | 'name' | 'avatar'> & {
    joined_at?: string | null;
};

export type BreadcrumbItemType = BreadcrumbItem;
