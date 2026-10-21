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
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
        $this->boardListService->create($board, $request->validated(), $request->user());

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
     * Permanently delete an archived list along with its cards. Lists are archived first, so nothing is deleted in
     * one step.
     *
     * @throws ValidationException when the list is not archived
     */
    public function destroy(Board $board, BoardList $boardList): HttpResponse
    {
        Gate::authorize('delete', $boardList);

        if (!$boardList->isArchived()) {
            throw ValidationException::withMessages(['board_list' => 'Only archived lists can be deleted.']);
        }

        $boardList->forceDelete();

        BoardChanged::dispatch($board);

        return response()->noContent();
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
        Gate::authorize('archive', $boardList);

        $this->boardListService->archive($boardList, auth()->user());

        return back();
    }

    public function unarchive(Board $board, BoardList $boardList): RedirectResponse
    {
        Gate::authorize('archive', $boardList);

        $this->boardListService->unarchive($boardList);

        return back();
    }
}
