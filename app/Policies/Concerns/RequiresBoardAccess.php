<?php

namespace App\Policies\Concerns;

use App\Models\Board;
use App\Models\User;
use Illuminate\Auth\Access\Response;

trait RequiresBoardAccess
{
    /**
     * Allow the workspace owner and the members added to the board. Everyone else, including workspace
     * members who aren't on it, is told the board doesn't exist rather than that it's off limits.
     */
    protected function onBoard(User $user, Board $board): Response
    {
        return $board->isAccessibleBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Allow the people on the board to change it while it is active. An archived board is read-only
     * until it is restored; everyone else is still told the board doesn't exist.
     */
    protected function onEditableBoard(User $user, Board $board): Response
    {
        $access = $this->onBoard($user, $board);

        if ($access->denied()) {
            return $access;
        }

        return $board->isArchived()
            ? Response::deny('This board is archived. Restore it to make changes.')
            : Response::allow();
    }
}
