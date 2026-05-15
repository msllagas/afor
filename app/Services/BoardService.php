<?php

namespace App\Services;

use App\Events\BoardAddedToWorkspace;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class BoardService
{
    private const array DEFAULT_LISTS = [
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

    public function archive(Board $board, User $archiver): Board
    {
        $board->update([
            'archived_by' => $archiver->id,
            'archived_at' => now(),
        ]);

        return $board;
    }

    public function unarchive(Board $board): Board
    {
        $board->update([
            'archived_by' => null,
            'archived_at' => null,
        ]);

        return $board;
    }

    private function createDefaultLists(Board $board): void
    {
        $board->boardLists()->createMany(self::DEFAULT_LISTS);
    }
}
