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

    /**
     * Take a member out of the workspace, whether the owner removes them or they leave. They lose what they kept
     * from it: their place on its boards, their stars on them, and the workspace as the one they last opened.
     * Rejoining later starts with no boards. Routes and policies make sure the user is a member.
     *
     * @throws \InvalidArgumentException when the user owns the workspace
     */
    public function removeMember(Workspace $workspace, User $user): void
    {
        // todo: implement a database-level mechanism that prevent the owner of the workspace to attach itself on its own workspace as a member
        if ($workspace->owner_id === $user->id) {
            throw new \InvalidArgumentException('Cannot remove the workspace owner.');
        }

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

    private function newInvitationToken(): string
    {
        return strtoupper(config('app.name')).str()->random(32);
    }
}
