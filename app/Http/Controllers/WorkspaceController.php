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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService
    ) {}

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
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

    public function home(Workspace $workspace): Response|RedirectResponse
    {
        $user = auth()->user();

        $canAccess = $workspace->owner_id === $user->id
            || $workspace->users()->whereKey($user->id)->exists();

        if (!$canAccess) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('workspaces/Home', [
            'workspace' => fn () => new WorkspaceResource($workspace->load('logoFile')),
            'members'   => fn () => UserResource::collection(
                $workspace->users()
                    ->select('users.id', 'users.name', 'users.email', 'users.email_verified_at')
                    ->with('avatarFile')
                    ->get()
            )->resolve(),
            'boards' => Inertia::defer(fn () => $workspace->boards()
                ->select('id', 'name', 'workspace_id', 'created_at')
                ->unarchived()
                ->withExists([
                    'favoritedByUsers as is_favorited' => fn ($query) => $query->whereKey($user->id),
                ])
                ->with([
                    'boardLists' => fn ($query) => $query
                        ->select('id', 'board_id', 'color', 'order')
                        ->active()
                        ->withCount('cards'),
                ])
                ->oldest()
                ->get()),
            'inviteLink' => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace, $user)),
        ]);
    }

    public function members(Workspace $workspace): Response|RedirectResponse
    {
        $user = auth()->user();

        if (!$workspace->isAccessibleBy($user)) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('workspaces/Member', [
            'workspace' => fn () => new WorkspaceResource($workspace->load('logoFile')),
            'owner'     => fn () => new WorkspaceMemberResource(
                $workspace->owner()->select('id', 'name', 'email')->with('avatarFile')->firstOrFail()
            )->resolve(),
            'members' => fn () => WorkspaceMemberResource::collection(
                $workspace->users()
                    ->select('users.id', 'users.name', 'users.email')
                    ->with('avatarFile')
                    ->orderBy('users.name')
                    ->get()
            )->resolve(),
            'canManageMembers' => fn () => $user->can('manageMembers', $workspace),
            'inviteLink'       => Inertia::defer(fn () => $this->workspaceService->generateInvitationLink($workspace, $user)),
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

    public function settings(Workspace $workspace): Response|RedirectResponse
    {
        if (auth()->user()->cannot('update', $workspace)) {
            return redirect()->route('dashboard');
        }

        $workspace->load('logoFile');

        return Inertia::render('workspaces/Settings', [
            'workspace' => new WorkspaceResource($workspace),
        ]);
    }
}
