<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Take the user to the workspace they last opened, or to all their boards when they can't open it.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        $lastWorkspace = $user->last_workspace_id ? Workspace::query()->find($user->last_workspace_id) : null;

        if ($lastWorkspace?->isAccessibleBy($user)) {
            return to_route('workspaces.home', $lastWorkspace);
        }

        return to_route('boards.index');
    }
}
