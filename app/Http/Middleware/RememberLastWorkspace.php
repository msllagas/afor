<?php

namespace App\Http\Middleware;

use App\Models\Board;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberLastWorkspace
{
    /**
     * Remember the workspace of the page the user just opened, so pages that
     * don't belong to a workspace (like the dashboard) can show it in the sidebar.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $workspaceId = self::workspaceIdFromRoute($request);

        if ($user
            && $workspaceId
            && $request->isMethod('GET')
            && $response->isSuccessful()
            && $user->last_workspace_id !== $workspaceId
        ) {
            $user->forceFill(['last_workspace_id' => $workspaceId])->saveQuietly();
        }

        return $response;
    }

    /**
     * The workspace the current route belongs to, if any.
     */
    public static function workspaceIdFromRoute(Request $request): ?string
    {
        $workspace = $request->route('workspace');

        if ($workspace instanceof Workspace) {
            return $workspace->id;
        }

        $board = $request->route('board');

        return $board instanceof Board ? $board->workspace_id : null;
    }
}
