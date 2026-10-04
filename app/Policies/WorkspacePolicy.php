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

    /**
     * Determine whether the user can remove members from the workspace.
     *
     * Only the workspace creator (its owner) can remove members for now,
     * including members trying to remove themselves.
     */
    public function manageMembers(User $user, Workspace $workspace): Response
    {
        if ($workspace->owner_id === $user->id) {
            return Response::allow();
        }

        return $workspace->isAccessibleBy($user)
            ? Response::deny('Only the workspace owner can remove members.')
            : Response::denyAsNotFound();
    }
}
