<?php

namespace App\Http\Controllers;

use App\Events\UserWorkspacesChanged;
use App\Events\WorkspaceChanged;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInvitationController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService
    ) {}

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
            ->validFor($workspace, $token)
            ->firstOrFail();

        return Inertia::render('workspace-invitations/Invite', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Join the workspace through its invite link. Links not issued by the owner, or reset since, are not found.
     */
    public function accept(Workspace $workspace, string $token): RedirectResponse
    {
        $invitationIsValid = WorkspaceInvitation::query()
            ->validFor($workspace, $token)
            ->exists();

        abort_unless($invitationIsValid, 404);

        $user = auth()->user();

        if (!$workspace->isAccessibleBy($user)) {
            $workspace->users()->attach($user->id);

            WorkspaceChanged::dispatch($workspace);
            UserWorkspacesChanged::dispatch($user);
        }

        return redirect()->route('workspaces.home', $workspace);
    }

    /**
     * Replace the workspace's invite link, so links already shared stop working.
     */
    public function reset(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('invite', $workspace);

        $this->workspaceService->resetInvitationLink($workspace);

        return back();
    }
}
