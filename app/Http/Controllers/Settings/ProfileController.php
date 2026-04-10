<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\File;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        $avatar = $request->file('avatar');

        $path = $avatar->store('avatars');

        $oldAvatarFile = $user->avatarFile()->first();
        if ($oldAvatarFile) {
            // Delete the old avatar file from the storage
            Storage::disk($oldAvatarFile->disk)->delete($oldAvatarFile->path);
            // Delete the old avatar file from the database
            $oldAvatarFile->delete();
        }

        File::query()->create([
            'fileable_id' => $user->id,
            'fileable_type' => User::class,
            'collection' => 'avatar',
            'disk' => config('filesystems.default'),
            'path' => $path,
            'original_filename' => $avatar->getClientOriginalName(),
            'mime_type' => $avatar->getMimeType(),
            'size' => $avatar->getSize(),
        ]);

        return back();
    }

    public function deleteAvatar(): RedirectResponse
    {
        $user = auth()->user();

        $avatarFile = $user->avatarFile()->first();
        if ($avatarFile) {
            // Delete the old avatar file from the storage
            Storage::disk($avatarFile->disk)->delete($avatarFile->path);
            // Delete the old avatar file from the database
            $avatarFile->delete();
        }

        return back();
    }
}
