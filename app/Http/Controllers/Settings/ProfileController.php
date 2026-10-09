<?php

namespace App\Http\Controllers\Settings;

use App\DTOs\FileUploadData;
use App\Enums\FileCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\UpdateAvatarRequest;
use App\Services\FileUploadService;
use App\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly UserService $userService,
    ) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status'          => $request->session()->get('status'),
            // The account deletion dialog names what goes with the account.
            'ownedWorkspacesToDelete' => fn () => $request->user()->ownedWorkspaces()
                ->select('id', 'name')
                ->withCount('users as members_count')
                ->orderBy('name')
                ->get()
                ->map->only('id', 'name', 'members_count'),
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

        if ($request->user()->wasChanged('name')) {
            $this->userService->announceProfileChange($request->user());
        }

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

        $this->userService->deleteAccount($user);

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

        $this->userService->announceProfileChange($request->user());

        return back();
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $this->fileUploadService->delete($request->user(), FileCollection::AVATAR);

        $this->userService->announceProfileChange($request->user());

        return back();
    }
}
