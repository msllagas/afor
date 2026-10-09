<?php

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
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
        $boardList = BoardList::query()->create([
            ...$data,
            'board_id' => $board->id,
            'order'    => ($board->boardLists()->max('order') ?? -1) + 1,
        ]);

        BoardChanged::dispatch($board);

        return $boardList;
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

        BoardChanged::dispatch($board);
    }

    public function archive(BoardList $boardList, User $archiver): BoardList
    {
        $boardList->update([
            'archived_by' => $archiver->id,
            'archived_at' => now(),
        ]);

        BoardChanged::dispatch($boardList->board);

        return $boardList;
    }

    /**
     * Bring the list back to the place it was archived from, along with its cards.
     */
    public function unarchive(BoardList $boardList): BoardList
    {
        $boardList->update([
            'archived_by' => null,
            'archived_at' => null,
        ]);

        BoardChanged::dispatch($boardList->board);

        return $boardList;
    }
}
