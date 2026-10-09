<?php

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Support\Facades\DB;

class CardService
{
    /**
     * Add a card at the bottom of the list.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(BoardList $boardList, array $data): Card
    {
        $card = Card::query()->create([
            ...$data,
            'board_list_id' => $boardList->id,
            'order'         => ($boardList->cards()->max('order') ?? -1) + 1,
        ]);

        BoardChanged::dispatch($boardList->board);

        return $card;
    }

    /**
     * Save the order of the list's cards. Cards of other lists are left alone.
     *
     * @param  array<int, array{id: string, order: int}>  $positions
     */
    public function reorder(BoardList $boardList, array $positions): void
    {
        DB::transaction(function () use ($boardList, $positions) {
            foreach ($positions as $position) {
                $boardList->cards()->whereKey($position['id'])->update(['order' => $position['order']]);
            }
        });

        BoardChanged::dispatch($boardList->board);
    }
}
