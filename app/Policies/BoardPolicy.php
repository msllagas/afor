<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\RequiresBoardAccess;
use Illuminate\Auth\Access\Response;

class BoardPolicy
{
    use RequiresBoardAccess;

    /**
     * Determine whether the user can open the board, star it and see its lists, cards and members.
     */
    public function view(User $user, Board $board): Response
    {
        return $this->onBoard($user, $board);
    }

    /**
     * Determine whether the user can add a board to the workspace.
     *
     * Only the owner can; members are refused and anyone else is told the workspace doesn't exist.
     */
    public function create(User $user, Workspace $workspace): Response
    {
        if ($workspace->owner_id === $user->id) {
            return Response::allow();
        }

        return $workspace->isAccessibleBy($user)
            ? Response::deny('Only the workspace owner can create boards.')
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can update the board, including renaming, archiving and reordering its lists.
     */
    public function update(User $user, Board $board): Response
    {
        return $this->onBoard($user, $board);
    }

    /**
     * Determine whether the user can permanently delete the board.
     */
    public function delete(User $user, Board $board): Response
    {
        return $this->ownerOnly($user, $board, 'Only the workspace owner can delete boards.');
    }

    /**
     * Determine whether the user can add workspace members to the board and remove them from it.
     */
    public function manageMembers(User $user, Board $board): Response
    {
        return $this->ownerOnly($user, $board, 'Only the workspace owner can add or remove board members.');
    }

    /**
     * Determine whether the user can leave the board.
     *
     * Members can leave; the owner can't, because they have every board in their workspace.
     */
    public function leave(User $user, Board $board): Response
    {
        if ($board->isOwnedBy($user)) {
            return Response::deny("You own this workspace, so you're on every board in it.");
        }

        return $this->onBoard($user, $board);
    }

    /**
     * Allow the workspace owner, deny board members with a 403 and hide the board from everyone else.
     */
    private function ownerOnly(User $user, Board $board, string $deniedMessage): Response
    {
        if ($board->isOwnedBy($user)) {
            return Response::allow();
        }

        return $board->isAccessibleBy($user)
            ? Response::deny($deniedMessage)
            : Response::denyAsNotFound();
    }
}
