<?php

namespace App\Http\Controllers\Settings;

use App\DTOs\FileUploadData;
use App\Enums\FileCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\UpdateAvatarRequest;
use App\Services\FileUploadService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status'          => $request->session()->get('status'),
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

        $this->fileUploadService->delete($user, FileCollection::AVATAR);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Replace the user's avatar. The current one is only removed once the new one is saved.
     */
    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $this->fileUploadService->replace(new FileUploadData(
            model: $request->user(),
            file: $request->file('avatar'),
            collection: FileCollection::AVATAR,
            path: 'avatars',
            uploadedBy: $request->user(),
        ));

        return back();
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $this->fileUploadService->delete($request->user(), FileCollection::AVATAR);

        return back();
    }
}
