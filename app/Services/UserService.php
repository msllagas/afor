<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Models\Workspace;

class UserService
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

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

        $user->delete();

        $this->fileUploadService->deleteMany($files);
    }
}
