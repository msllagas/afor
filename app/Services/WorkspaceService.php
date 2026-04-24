<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;

class WorkspaceService
{
    public function generateInvitationLink(Workspace $workspace, User $user): string
    {

        $invitation = WorkspaceInvitation::query()
            ->firstOrCreate([
                'workspace_id' => $workspace->id,
                'invited_by' => $user->id,
            ], [
                'token' => strtoupper(config('app.name')).str()->random(32),
            ]);

        return route('workspace-invitations.show', [$workspace, $invitation->token]);
    }

    public function removeMember(Workspace $workspace, User $user): void
    {
        // todo: implement a database-level mechanism that prevent the owner of the workspace to attach itself on its own workspace as a member
        if ($workspace->owner_id === $user->id) {
            throw new \InvalidArgumentException('Cannot remove the workspace owner.');
        }

        if (! $workspace->users()->where('user_id', $user->id)->exists()) {
            throw new \InvalidArgumentException('User is not a member of this workspace.');
        }

        $workspace->users()->detach($user->id);

    }
}
