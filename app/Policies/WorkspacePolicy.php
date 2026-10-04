<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\RequiresWorkspaceMembership;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    use RequiresWorkspaceMembership;

    /**
     * Determine whether the user can view the workspace and its boards.
     */
    public function view(User $user, Workspace $workspace): Response
    {
        return $this->memberOf($user, $workspace);
    }

    /**
     * Determine whether the user can change the workspace settings (name, description and logo).
     */
    public function update(User $user, Workspace $workspace): Response
    {
        return $this->ownerOnly($user, $workspace, 'Only the workspace owner can change its settings.');
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): Response
    {
        return $this->ownerOnly($user, $workspace, 'Only the workspace owner can delete it.');
    }

    /**
     * Determine whether the user can remove members from the workspace.
     *
     * Only the workspace creator (its owner) can remove members for now,
     * including members trying to remove themselves.
     */
    public function manageMembers(User $user, Workspace $workspace): Response
    {
        return $this->ownerOnly($user, $workspace, 'Only the workspace owner can remove members.');
    }

    /**
     * Determine whether the user can leave the workspace.
     *
     * Members can leave; the owner can't, because a workspace always needs one. Anyone else is told it doesn't exist.
     */
    public function leave(User $user, Workspace $workspace): Response
    {
        if ($workspace->owner_id === $user->id) {
            return Response::deny("You own this workspace, so you can't leave it. Delete it from its settings instead.");
        }

        return $workspace->users()->whereKey($user->id)->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can invite people, which means seeing and resetting the invite link.
     */
    public function invite(User $user, Workspace $workspace): Response
    {
        return $this->ownerOnly($user, $workspace, 'Only the workspace owner can invite people.');
    }

    /**
     * Allow the workspace owner, deny members with a 403 and hide the workspace from everyone else.
     */
    private function ownerOnly(User $user, Workspace $workspace, string $deniedMessage): Response
    {
        if ($workspace->owner_id === $user->id) {
            return Response::allow();
        }

        return $workspace->isAccessibleBy($user)
            ? Response::deny($deniedMessage)
            : Response::denyAsNotFound();
    }
}
