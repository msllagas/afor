<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInvitationController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param Workspace $workspace
     * @param string $token
     * @return Response
     */
    public function show(Workspace $workspace, string $token): Response
    {
        $invitation = WorkspaceInvitation::query()
            ->select('token', 'invited_by', 'workspace_id')
            ->with([
                'inviter:id,name',
                'workspace:id,name',
            ])
            ->where('workspace_id', $workspace->id)
            ->where('token', $token)
            ->firstOrFail();

        return Inertia::render('workspace-invitations/Invite', [
            'invitation' => $invitation,
        ]);
    }
}
