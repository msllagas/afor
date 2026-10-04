<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\RequiresWorkspaceMembership;
use Illuminate\Auth\Access\Response;

class BoardPolicy
{
    use RequiresWorkspaceMembership;

    /**
     * Determine whether the user can open the board, star it and see its lists and cards.
     */
    public function view(User $user, Board $board): Response
    {
        return $this->memberOf($user, $board->workspace);
    }

    /**
     * Determine whether the user can add a board to the workspace.
     */
    public function create(User $user, Workspace $workspace): Response
    {
        return $this->memberOf($user, $workspace);
    }

    /**
     * Determine whether the user can update the board, including renaming, archiving and reordering its lists.
     */
    public function update(User $user, Board $board): Response
    {
        return $this->memberOf($user, $board->workspace);
    }

    /**
     * Determine whether the user can permanently delete the board.
     */
    public function delete(User $user, Board $board): Response
    {
        return $this->memberOf($user, $board->workspace);
    }
}
