<?php

namespace App\Http\Controllers;

use App\Events\BoardChanged;
use App\Http\Requests\ReorderBoardListsRequest;
use App\Http\Requests\StoreBoardListRequest;
use App\Http\Requests\UpdateBoardListRequest;
use App\Models\Board;
use App\Models\BoardList;
use App\Services\BoardListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BoardListController extends Controller
{
    public function __construct(
        private readonly BoardListService $boardListService,
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBoardListRequest $request, Board $board): RedirectResponse
    {
        $this->boardListService->create($board, $request->validated());

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBoardListRequest $request, Board $board, BoardList $boardList): RedirectResponse
    {
        $boardList->update($request->validated());

        BoardChanged::dispatch($board);

        return back();
    }

    /**
     * Save the order of the board's lists. Lists of other boards in the payload are left alone.
     */
    public function reorder(ReorderBoardListsRequest $request, Board $board): RedirectResponse
    {
        $this->boardListService->reorder($board, $request->validated('boardLists'));

        return back();
    }

    public function archive(Board $board, BoardList $boardList): RedirectResponse
    {
        Gate::authorize('update', $boardList);

        $this->boardListService->archive($boardList, auth()->user());

        return back();
    }

    public function unarchive(Board $board, BoardList $boardList): RedirectResponse
    {
        Gate::authorize('update', $boardList);

        $this->boardListService->unarchive($boardList);

        return back();
    }
}
