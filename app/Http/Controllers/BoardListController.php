<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardListRequest;
use App\Http\Requests\UpdateBoardListRequest;
use App\Models\Board;
use App\Models\BoardList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoardListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

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
                'order' => $nextOrder,
            ]
        ));

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(BoardList $boardList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BoardList $boardList)
    {
        //
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
     * Remove the specified resource from storage.
     */
    public function destroy(BoardList $boardList)
    {
        //
    }
}
