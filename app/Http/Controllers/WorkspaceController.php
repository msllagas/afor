<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceService;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function home(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->user_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        $members = $workspace->users()
            ->select('users.id', 'users.name')
            ->get();

        if (! $isOwner && ! $isMember) {
            return redirect()->route('dashboard');
        }

        $workspace->load('boards');

        return Inertia::render('workspaces/Home', [
            'workspace' => $workspace,
            'members' => $members,
        ]);
    }

    public function members(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->user_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            return redirect()->route('dashboard');
        }

        $owner = $workspace->user()
            ->select(['id', 'name'])
            ->first();

        $members = $workspace->users()
            ->select('users.id', 'users.name')
            ->get();

        return Inertia::render('workspaces/Member', [
            'workspace' => $workspace,
            'owner' => $owner,
            'members' => $members,
            'inviteLink' => Inertia::defer(fn () => app(WorkspaceService::class)->generateInvitationLink($workspace, auth()->user())),
        ]);
    }
}
