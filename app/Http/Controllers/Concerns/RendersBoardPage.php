<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\BoardListColor;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

trait RendersBoardPage
{
    /**
     * Render the board page with its lists, cards and members, optionally with a card open.
     *
     * Only the workspace owner receives the workspace members who can still be added to the board.
     */
    protected function renderBoardPage(Board $board, User $user, ?Card $selectedCard = null): Response
    {
        $canManageMembers = $user->can('manageMembers', $board);

        return Inertia::render('boards/Show', [
            'board' => fn () => $board->load([
                'boardLists' => fn ($query) => $query->with('cards')->active(),
            ]),
            'selectedCard' => $selectedCard,
            'colors'       => Inertia::once(fn () => BoardListColor::cases()),
            'owner'        => fn () => new WorkspaceMemberResource(
                $board->workspace->owner()->select('id', 'name', 'email')->with('avatarFile')->firstOrFail()
            )->resolve(),
            'members' => fn () => WorkspaceMemberResource::collection(
                $board->members()
                    ->select('users.id', 'users.name', 'users.email')
                    ->with('avatarFile')
                    ->orderBy('users.name')
                    ->get()
            )->resolve(),
            'canManageMembers' => $canManageMembers,
            // Members never receive the rest of the workspace, not even through a partial reload that asks for it.
            ...($canManageMembers ? [
                'addableMembers' => fn () => WorkspaceMemberResource::collection(
                    $board->workspace->users()
                        ->select('users.id', 'users.name', 'users.email')
                        ->whereDoesntHave('sharedBoards', fn ($query) => $query->whereKey($board->id))
                        ->with('avatarFile')
                        ->orderBy('users.name')
                        ->get()
                )->resolve(),
            ] : []),
        ]);
    }
}
