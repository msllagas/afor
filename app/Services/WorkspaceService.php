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
                'token' => strtoupper(config('app.name')) . str()->random(32),
            ]);

        return route('workspace-invitations.show', [$workspace, $invitation->token]);
    }

}
