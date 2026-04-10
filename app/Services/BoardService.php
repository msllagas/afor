<?php

namespace App\Services;

use App\Events\BoardAddedToWorkspace;
use App\Models\Board;
use App\Models\Workspace;

class BoardService
{
    private const DEFAULT_LISTS = [
        ['name' => 'To Do', 'order' => 0],
        ['name' => 'In Progress', 'order' => 1],
        ['name' => 'Done', 'order' => 2],
    ];

    public function create(array $data, Workspace $workspace)
    {
        $board = Board::create([
            ...$data,
            'workspace_id' => $workspace->id,
        ]);

        $this->createDefaultLists($board);

        BoardAddedToWorkspace::dispatch($board, $workspace->id);

        return $board->load('boardLists.cards');
    }

    private function createDefaultLists(Board $board): void
    {
        $board->boardLists()->createMany(self::DEFAULT_LISTS);
    }
}
