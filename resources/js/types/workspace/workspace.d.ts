export interface Card {
    id: string;
    name: string;
    description?: string | null;
    order: number;
    board_list_id: string;
}

export interface BoardList {
    id: string;
    name: string;
    order: number;
    board_id: string;
    cards: Card[];
    cards_count?: number;
    color?: string | null;
}

export interface Board {
    id: string;
    name: string;
    workspace_id: string;
    board_lists: BoardList[];
    is_favorited?: boolean;
    created_at: string;
    archived_at?: number | null;
    archiver?: { id: string; name: string } | null;
}

/** The payload of vuedraggable's `change` event. */
export interface SortableChangeEvent<T> {
    moved?: { element: T; oldIndex: number; newIndex: number };
    added?: { element: T; newIndex: number };
    removed?: { element: T; oldIndex: number };
}

export type StarredBoard = Pick<Board, 'id' | 'name' | 'workspace_id'>;

export interface Workspace {
    id: string;
    name: string;
    description?: string;
    boards: Board[];
    logo?: string; // url path
}

/** A workspace and its boards that pass the boards page's search and scope. */
export interface WorkspaceBoardsMatch {
    workspace: Workspace;
    boards: Board[];
}

export interface WorkspaceBoardsGroup {
    id: 'current' | 'owned' | 'shared';
    title: string;
    matches: WorkspaceBoardsMatch[];
}

/** Which workspaces' boards the boards page shows. */
export type BoardsScope = 'all' | 'owned' | 'shared' | 'starred';

export type BoardsView = 'grid' | 'list';
