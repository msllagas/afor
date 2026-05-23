export interface Card {
    id: string;
    name: string;
    description?: string;
    order: number;
    board_list_id: string;
}

export interface BoardList {
    id: string;
    name: string;
    order: number;
    board_id: string;
    cards: Card[];
    color?: string;
}

export interface Board {
    id: string;
    name: string;
    workspace_id: string;
    board_lists: BoardList[];
    is_favorited?: boolean;
    created_at: string;
}

export interface Workspace {
    id: string;
    name: string;
    description?: string;
    boards: Board[];
    logo?: string; // url path
}
