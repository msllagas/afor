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
}
