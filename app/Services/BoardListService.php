<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardList;
use Illuminate\Support\Facades\DB;

class BoardListService
{
    /**
     * Add a list at the end of the board.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Board $board, array $data): BoardList
    {
        return BoardList::query()->create([
            ...$data,
            'board_id' => $board->id,
            'order'    => ($board->boardLists()->max('order') ?? -1) + 1,
        ]);
    }

    /**
     * Save the order of the board's lists. Lists of other boards are left alone.
     *
     * @param  array<int, array{id: string, order: int}>  $positions
     */
    public function reorder(Board $board, array $positions): void
    {
        DB::transaction(function () use ($board, $positions) {
            foreach ($positions as $position) {
                $board->boardLists()->whereKey($position['id'])->update(['order' => $position['order']]);
            }
        });
    }
}
