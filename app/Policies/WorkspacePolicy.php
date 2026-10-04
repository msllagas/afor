<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view the workspace and its boards.
     */
    public function view(User $user, Workspace $workspace): Response
    {
        return $workspace->isAccessibleBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
