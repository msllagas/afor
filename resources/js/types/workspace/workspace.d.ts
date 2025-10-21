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
    cards: Card[];
}

export interface Board {
    id: string;
    name: string;
    board_lists: BoardList[];
}

export interface Workspace {
    id: string;
    name: string;
    boards: Board[];
}
