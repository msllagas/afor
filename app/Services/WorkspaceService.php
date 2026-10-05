<?php

namespace App\Services;

use App\Enums\FileCollection;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\DB;

class WorkspaceService
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    /**
     * Get the workspace's invite link, creating it on first use. Only the owner can invite people.
     *
     * @throws \InvalidArgumentException when the user is not the workspace owner
     */
    public function generateInvitationLink(Workspace $workspace, User $user): string
    {
        if ($workspace->owner_id !== $user->id) {
            throw new \InvalidArgumentException('Only the workspace owner can invite people.');
        }

        $invitation = WorkspaceInvitation::query()
            ->firstOrCreate([
                'workspace_id' => $workspace->id,
                'invited_by'   => $user->id,
            ], [
                'token' => $this->newInvitationToken(),
            ]);

        return route('workspace-invitations.show', [$workspace, $invitation->token]);
    }

    /**
     * Replace the workspace's invite link so every link shared before stops working.
     */
    public function resetInvitationLink(Workspace $workspace): string
    {
        $invitation = DB::transaction(function () use ($workspace) {
            WorkspaceInvitation::query()->whereBelongsTo($workspace)->delete();

            return WorkspaceInvitation::query()->create([
                'workspace_id' => $workspace->id,
                'invited_by'   => $workspace->owner_id,
                'token'        => $this->newInvitationToken(),
            ]);
        });

        return route('workspace-invitations.show', [$workspace, $invitation->token]);
    }

    public function removeMember(Workspace $workspace, User $user): void
    {
        // todo: implement a database-level mechanism that prevent the owner of the workspace to attach itself on its own workspace as a member
        if ($workspace->owner_id === $user->id) {
            throw new \InvalidArgumentException('Cannot remove the workspace owner.');
        }

        if (!$workspace->users()->where('user_id', $user->id)->exists()) {
            throw new \InvalidArgumentException('User is not a member of this workspace.');
        }

        $this->endMembership($workspace, $user);
    }

    /**
     * Take the user out of a workspace they're a member of, at their own request.
     *
     * @throws \InvalidArgumentException when the user owns the workspace or isn't a member of it
     */
    public function leaveWorkspace(Workspace $workspace, User $user): void
    {
        if ($workspace->owner_id === $user->id) {
            throw new \InvalidArgumentException('The workspace owner cannot leave it.');
        }

        if (!$workspace->users()->whereKey($user->id)->exists()) {
            throw new \InvalidArgumentException('User is not a member of this workspace.');
        }

        $this->endMembership($workspace, $user);
    }

    /**
     * Permanently delete the workspace. Its boards, lists, cards, memberships
     * and invitations cascade at the database level; the logo file is removed
     * from storage once the workspace is gone.
     */
    public function deleteWorkspace(Workspace $workspace): void
    {
        $workspace->delete();

        $this->fileUploadService->delete($workspace, FileCollection::WORKSPACE_LOGO);
    }

    /**
     * Remove the membership along with what the user kept from it: their place on its boards, their stars
     * on them, and the workspace as the one they last opened. Rejoining later starts with no boards.
     */
    private function endMembership(Workspace $workspace, User $user): void
    {
        DB::transaction(function () use ($workspace, $user) {
            $workspace->users()->detach($user->id);

            $boardIds = $workspace->boards()->withTrashed()->pluck('id');

            $user->sharedBoards()->detach($boardIds);
            $user->favoriteBoards()->detach($boardIds);

            if ($user->last_workspace_id === $workspace->id) {
                $user->forceFill(['last_workspace_id' => null])->saveQuietly();
            }
        });
    }

    private function newInvitationToken(): string
    {
        return strtoupper(config('app.name')).str()->random(32);
    }
}
