<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardMemberRequest;
use App\Models\Board;
use App\Models\User;
use App\Services\BoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BoardMemberController extends Controller
{
    public function __construct(
        private readonly BoardService $boardService,
    ) {}

    /**
     * Add a workspace member to the board.
     */
    public function store(StoreBoardMemberRequest $request, Board $board): RedirectResponse
    {
        $this->boardService->addMember($board, User::query()->findOrFail($request->validated('user_id')));

        return back();
    }

    /**
     * Take a member off the board. They stay in the workspace.
     */
    public function destroy(Board $board, User $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $board);

        $this->boardService->removeMember($board, $member);

        return back();
    }

    /**
     * Leave a board the user was added to, then land on its workspace, which they still belong to.
     */
    public function leave(Board $board): RedirectResponse
    {
        Gate::authorize('leave', $board);

        $this->boardService->removeMember($board, auth()->user());

        return to_route('workspaces.home', $board->workspace_id);
    }
}
