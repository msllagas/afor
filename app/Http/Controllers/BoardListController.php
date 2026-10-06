<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderBoardListsRequest;
use App\Http\Requests\StoreBoardListRequest;
use App\Http\Requests\UpdateBoardListRequest;
use App\Models\Board;
use App\Models\BoardList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BoardListController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBoardListRequest $request, Board $board): RedirectResponse
    {
        $nextOrder = $board->boardLists()->max('order');
        $nextOrder = is_null($nextOrder) ? 0 : $nextOrder + 1;
        BoardList::query()->create(array_merge(
            $request->validated(),
            [
                'board_id' => $board->id,
                'order'    => $nextOrder,
            ]
        ));

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBoardListRequest $request, Board $board, BoardList $boardList): RedirectResponse
    {
        $boardList->update($request->validated());

        return back();
    }

    /**
     * Save the order of the board's lists. Lists of other boards in the payload are left alone.
     */
    public function reorder(ReorderBoardListsRequest $request, Board $board): RedirectResponse
    {
        DB::transaction(function () use ($request, $board) {
            foreach ($request->validated('boardLists') as $boardList) {
                BoardList::query()->where('id', $boardList['id'])
                    ->where('board_id', $board->id)
                    ->update(['order' => $boardList['order']]);
            }
        });

        return back();
    }
}
