<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Validation\ValidationException;

class UsageLimits
{
    /**
     * Boards in the workspace, archived ones included, against the cap.
     *
     * @return array{used: int, limit: int}
     */
    public function boardUsage(Workspace $workspace): array
    {
        return [
            'used'  => $workspace->boards()->count(),
            'limit' => config('limits.boards_per_workspace'),
        ];
    }

    /**
     * Members of the workspace, the owner not included, against the cap.
     *
     * @return array{used: int, limit: int}
     */
    public function memberUsage(Workspace $workspace): array
    {
        return [
            'used'  => $workspace->users()->count(),
            'limit' => config('limits.members_per_workspace'),
        ];
    }

    public function isWorkspaceFull(Workspace $workspace): bool
    {
        $usage = $this->memberUsage($workspace);

        return $usage['used'] >= $usage['limit'];
    }

    /**
     * @throws ValidationException when the workspace has no room for another board
     */
    public function ensureRoomForBoard(Workspace $workspace): void
    {
        $usage = $this->boardUsage($workspace);

        if ($usage['used'] >= $usage['limit']) {
            throw ValidationException::withMessages([
                'name' => "This workspace has reached its limit of {$usage['limit']} boards. Delete archived boards to make room.",
            ]);
        }
    }

    /**
     * @throws ValidationException when the workspace is full or the user has joined as many workspaces as allowed
     */
    public function ensureCanJoin(Workspace $workspace, User $user): void
    {
        if ($this->isWorkspaceFull($workspace)) {
            throw ValidationException::withMessages([
                'invitation' => 'This workspace is full. Ask the owner to make room for you.',
            ]);
        }

        $joinedLimit = config('limits.joined_workspaces_per_user');

        if ($user->sharedWorkspaces()->count() >= $joinedLimit) {
            throw ValidationException::withMessages([
                'invitation' => "You can be a member of up to {$joinedLimit} workspaces. Leave one to join this one.",
            ]);
        }
    }
}
