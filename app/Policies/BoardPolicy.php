<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoardPolicy
{
    /**
     * Determine whether the user can update the board, including archiving and unarchiving it.
     */
    public function update(User $user, Board $board): Response
    {
        return $this->ensureWorkspaceAccess($user, $board);
    }

    /**
     * Determine whether the user can permanently delete the board.
     */
    public function delete(User $user, Board $board): Response
    {
        return $this->ensureWorkspaceAccess($user, $board);
    }

    private function ensureWorkspaceAccess(User $user, Board $board): Response
    {
        return $board->workspace->isAccessibleBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
