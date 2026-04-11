<?php

namespace App\Services;

use App\Enums\FileCollection;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserAvatarService
{
    public function update(User $user, UploadedFile $file): void
    {

        $disk = config('filesystems.default');
        $path = $file->store('avatars', $disk);

        $this->deleteExistingAvatar($user);

        File::query()->create([
            'fileable_id' => $user->id,
            'fileable_type' => User::class,
            'collection' => FileCollection::AVATAR->value,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function delete(User $user): void
    {
        $this->deleteExistingAvatar($user);
    }

    private function deleteExistingAvatar(User $user): void
    {
        $avatarFile = $user->avatarFile()->first();

        if ($avatarFile) {
            // Delete the old avatar file from the storage
            Storage::disk($avatarFile->disk)->delete($avatarFile->path);
            // Delete the old avatar file from the database
            $avatarFile->delete();
        }
    }
}
