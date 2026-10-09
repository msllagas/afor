<?php

namespace App\Services;

use App\Events\BoardChanged;
use App\Events\WorkspaceChanged;
use App\Models\Board;
use App\Models\File;
use App\Models\User;
use App\Models\Workspace;

class UserService
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    public function announceProfileChange(User $user): void
    {
        Workspace::query()
            ->select('id')
            ->accessibleBy($user)
            ->get()
            ->each(fn (Workspace $workspace) => WorkspaceChanged::dispatch($workspace));
    }

    /**
     * Delete the account along with the workspaces it owns, which takes their boards away from every member.
     * The avatar and workspace logos are removed from storage once the records they belong to are gone.
     */
    public function deleteAccount(User $user): void
    {
        $files = File::query()
            ->where(fn ($query) => $query->where('fileable_type', User::class)->where('fileable_id', $user->id))
            ->orWhere(fn ($query) => $query
                ->where('fileable_type', Workspace::class)
                ->whereIn('fileable_id', $user->ownedWorkspaces()->select('id'))
            )
            ->get();

        $boards = Board::query()
            ->select('id', 'workspace_id')
            ->whereIn('workspace_id', $user->ownedWorkspaces()->select('id'))
            ->orWhereHas('members', fn ($query) => $query->whereKey($user->id))
            ->get();

        $workspaces = Workspace::query()->select('id')->accessibleBy($user)->get();

        $user->delete();

        $boards->each(fn (Board $board) => BoardChanged::dispatch($board));
        $workspaces->each(fn (Workspace $workspace) => WorkspaceChanged::dispatch($workspace));

        $this->fileUploadService->deleteMany($files);
    }
}
