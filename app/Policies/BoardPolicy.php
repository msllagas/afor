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
     * Determine whether the user can see the workspace's archived boards.
     *
     * Only the owner can; members are refused and anyone else is told the workspace doesn't exist.
     */
    public function viewArchived(User $user, Workspace $workspace): Response
    {
        if ($workspace->owner_id === $user->id) {
            return Response::allow();
        }

        return $workspace->isAccessibleBy($user)
            ? Response::deny('Only the workspace owner can see archived boards.')
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can update the board, including renaming it and reordering its lists.
     * Archived boards are read-only.
     */
    public function update(User $user, Board $board): Response
    {
        return $this->onEditableBoard($user, $board);
    }

    /**
     * Determine whether the user can star or unstar the board. Archived boards keep their stars as they are.
     */
    public function favorite(User $user, Board $board): Response
    {
        return $this->onEditableBoard($user, $board);
    }

    /**
     * Determine whether the user can archive the board. Only the owner can, and only once.
     */
    public function archive(User $user, Board $board): Response
    {
        $access = $this->ownerOnly($user, $board, 'Only the workspace owner can archive boards.');

        if ($access->denied()) {
            return $access;
        }

        return $this->onEditableBoard($user, $board);
    }

    /**
     * Determine whether the user can restore the board from the archive.
     */
    public function unarchive(User $user, Board $board): Response
    {
        return $this->ownerOnly($user, $board, 'Only the workspace owner can restore boards.');
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
     * Archived boards keep their members as they are.
     */
    public function manageMembers(User $user, Board $board): Response
    {
        $access = $this->ownerOnly($user, $board, 'Only the workspace owner can add or remove board members.');

        if ($access->denied()) {
            return $access;
        }

        return $this->onEditableBoard($user, $board);
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
