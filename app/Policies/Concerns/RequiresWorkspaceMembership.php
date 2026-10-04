<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

trait RequiresWorkspaceMembership
{
    /**
     * Allow the workspace owner and its members. Everyone else, including people who have left
     * or were removed, is told the record doesn't exist rather than that it's off limits.
     */
    protected function memberOf(User $user, Workspace $workspace): Response
    {
        return $workspace->isAccessibleBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
