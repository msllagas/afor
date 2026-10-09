<?php

namespace App\Http\Controllers;

use App\DTOs\FileUploadData;
use App\Enums\FileCollection;
use App\Events\WorkspaceChanged;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Services\FileUploadService;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly FileUploadService $fileUploadService,
    ) {}

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->update($request->safe()->only(['name', 'description']));

        if ($request->hasFile('logo')) {
            $this->fileUploadService->replace(new FileUploadData(
                model: $workspace,
                file: $request->file('logo'),
                collection: FileCollection::WORKSPACE_LOGO,
                path: "workspaces/{$workspace->id}/logo",
                uploadedBy: $request->user()
            ));
        } elseif ($request->boolean('remove_logo')) {
            $this->fileUploadService->delete($workspace, FileCollection::WORKSPACE_LOGO);
        }

        WorkspaceChanged::dispatch($workspace);

        return back();
    }

    public function destroy(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('delete', $workspace);

        $this->workspaceService->deleteWorkspace($workspace);

        return redirect()->route('dashboard');
    }

    public function home(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        $user = auth()->user();
        $canInvite = $user->can('invite', $workspace);

        return Inertia::render('workspaces/Home', [
            'workspace' => fn () => new WorkspaceResource($workspace->load('logoFile')),
            'members'   => fn () => WorkspaceMemberResource::collection(
                $workspace->users()
                    ->select('users.id', 'users.name')
                    ->with('avatarFile')
                    ->get()
            )->resolve(),
            'boards' => Inertia::defer(fn () => $workspace->boards()
                ->select('id', 'name', 'workspace_id', 'created_at')
                ->unarchived()
                ->visibleTo($user)
                ->withExists([
                    'favoritedByUsers as is_favorited' => fn ($query) => $query->whereKey($user->id),
                ])
                ->with([
                    'boardLists' => fn ($query) => $query
                        ->select('id', 'board_id', 'color', 'order')
                        ->unarchived()
                        ->withCount('cards'),
                ])
                ->oldest()
                ->get()),
            'canInvite'       => $canInvite,
            'canCreateBoards' => $user->can('create', [Board::class, $workspace]),
            // Archived boards are listed for the owner only; members can still open the ones they're on by URL.
            'canViewArchivedBoards' => $user->can('viewArchived', [Board::class, $workspace]),
            ...($canInvite ? [
                'inviteLink' => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace, $user)),
            ] : []),
        ]);
    }

    public function members(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        $user = auth()->user();
        $canInvite = $user->can('invite', $workspace);

        return Inertia::render('workspaces/Member', [
            'workspace' => fn () => new WorkspaceResource($workspace->load('logoFile')),
            'owner'     => fn () => new WorkspaceMemberResource(
                $workspace->owner()->select('id', 'name')->with('avatarFile')->firstOrFail()
            )->resolve(),
            'members' => fn () => WorkspaceMemberResource::collection(
                $workspace->users()
                    ->select('users.id', 'users.name')
                    ->with('avatarFile')
                    ->orderBy('users.name')
                    ->get()
            )->resolve(),
            'canManageMembers' => fn () => $user->can('manageMembers', $workspace),
            'canInvite'        => $canInvite,
            // Members never receive the link, not even through a partial reload that asks for it.
            ...($canInvite ? [
                'inviteLink' => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace, $user)),
            ] : []),
        ]);
    }

    public function removeMember(Workspace $workspace, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $workspace);

        try {
            $this->workspaceService->removeMember($workspace, $user);
        } catch (InvalidArgumentException $exception) {
            abort(403, $exception->getMessage());
        }

        return back();
    }

    /**
     * Leave a workspace the user is a member of, then land on the boards they can still reach.
     */
    public function leave(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('leave', $workspace);

        $this->workspaceService->removeMember($workspace, auth()->user());

        return to_route('boards.index');
    }

    public function settings(Workspace $workspace): Response
    {
        Gate::authorize('update', $workspace);

        return Inertia::render('workspaces/Settings', [
            'workspace'   => fn () => new WorkspaceResource($workspace->load('logoFile')),
            'boardCount'  => fn () => $workspace->boards()->count(),
            'memberCount' => fn () => $workspace->users()->count(),
        ]);
    }
}
