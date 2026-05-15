<?php

namespace App\Http\Controllers;

use App\DTOs\FileUploadData;
use App\Enums\FileCollection;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkspaceMemberResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\FileUploadService;
use App\Services\WorkspaceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService
    ) {}

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $isMember = $workspace->users()->where('user_id', $request->user()->id)->exists();
        $isOwner = $workspace->owner_id === $request->user()->id;

        if (!$isMember && !$isOwner) {
            throw new AuthorizationException('You are not authorized to update this workspace.', 403);
        }

        $workspace->update($request->safe()->only(['name', 'description']));

        if ($request->hasFile('logo')) {

            $path = "workspaces/{$workspace->id}/logo";

            $fileUploadData = new FileUploadData(
                model: $workspace,
                file: $request->file('logo'),
                collection: FileCollection::WORKSPACE_LOGO,
                path: $path,
                uploadedBy: auth()->user()
            );

            app(FileUploadService::class)->replace($fileUploadData);
        }

        return back();
    }

    public function home(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->owner_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOwner && !$isMember) {
            return redirect()->route('dashboard');
        }

        $members = UserResource::collection(
            $workspace->users()
                ->with('avatarFile')
                ->select('users.id', 'users.name', 'users.email', 'users.email_verified_at')
                ->get()
        )->resolve();

        $workspace->load([
            'logoFile',
        ]);

        return Inertia::render('workspaces/Home', [
            'boards' => Inertia::defer(function () use ($workspace) {
                return $workspace->boards()->unarchived()->get();
            }, 'boards'),
            'workspace'  => new WorkspaceResource($workspace),
            'members'    => $members,
            'inviteLink' => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace,
                auth()->user()), 'inviteLink'),
        ]);
    }

    public function members(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->owner_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOwner && !$isMember) {
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
            'workspace'  => $workspace,
            'owner'      => $owner,
            'members'    => $members,
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

    public function settings(Workspace $workspace): Response
    {
        $workspace->load('logoFile');

        return Inertia::render('workspaces/Settings', [
            'workspace' => new WorkspaceResource($workspace),
        ]);
    }
}
