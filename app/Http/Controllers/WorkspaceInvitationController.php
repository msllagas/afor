<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInvitationController extends Controller
{
    /**
     * Display the specified resource.
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

    public function accept(Workspace $workspace, string $token): RedirectResponse
    {
        $invitationExists = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->where('token', $token)
            ->exists();

        $authUser = auth()->user();

        if (! $invitationExists) {
            dd('do something when invitation does not exists');
        }

        $userExistInWorkspace = $workspace->users()
            ->where('user_id', $authUser->id);

        // Only attach user if it does not exist yet in workspace
        if (! $userExistInWorkspace->exists()) {
            $workspace->users()->attach($authUser->id);
        }

        $workspace->load('boards');

        return redirect()->route('workspaces.home', $workspace);

    }
}
