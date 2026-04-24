<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService
    ) {}

    public function home(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->owner_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            return redirect()->route('dashboard');
        }

        $members = UserResource::collection(
            $workspace->users()
                ->with('avatarFile')
                ->select('users.id', 'users.name', 'users.email')
                ->get()
        )->resolve();

        $workspace->load('boards');

        return Inertia::render('workspaces/Home', [
            'workspace' => $workspace,
            'members' => $members,
            'inviteLink' => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace,
                auth()->user())),
        ]);
    }

    public function members(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->owner_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            return redirect()->route('dashboard');
        }

        $owner = new WorkspaceMemberResource(
            $workspace->owner()->select(['id', 'name'])->first()->load('avatarFile')
        )->resolve();

        $members = WorkspaceMemberResource::collection(
            $workspace->users()
                ->with('avatarFile')
                ->select('users.id', 'users.name')
                ->get()
        )->resolve();

        return Inertia::render('workspaces/Member', [
            'workspace' => $workspace,
            'owner' => $owner,
            'members' => $members,
            'inviteLink' => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace,
                auth()->user())),
        ]);
    }

    public function removeMember(Workspace $workspace, User $user): RedirectResponse
    {
        try {
            $this->workspaceService->removeMember($workspace, $user);
        } catch (\InvalidArgumentException $e) {
            abort(403, $e->getMessage());
        }

        return back();
    }
}
