<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Board;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name'   => config('app.name'),
            'appUrl' => config('app.url'),
            'auth'   => [
                'user' => $user ? new UserResource($user->loadMissing('avatarFile')) : null,
            ],
            'sidebarOpen'      => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sharedWorkspaces' => fn () => $user
                ? WorkspaceResource::collection(
                    $user->sharedWorkspaces()->with('logoFile')->get()
                )->resolve()
                : [],
            'ownedWorkspaces' => fn () => $user
                ? WorkspaceResource::collection(
                    $user->ownedWorkspaces()->with('logoFile')->get()
                )->resolve()
                : [],
            'currentWorkspaceId' => fn () => $user
                ? RememberLastWorkspace::workspaceIdFromRoute($request) ?? $user->last_workspace_id
                : null,
            'starredBoards' => fn () => $user
                ? $user->favoriteBoards()
                    ->select('boards.id', 'boards.name', 'boards.workspace_id')
                    ->unarchived()
                    ->visibleTo($user)
                    ->orderBy('boards.name')
                    ->get()
                    ->map(fn (Board $board) => $board->only('id', 'name', 'workspace_id'))
                : [],
        ];
    }
}
