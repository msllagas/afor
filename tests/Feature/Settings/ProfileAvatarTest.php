<?php

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function PHPUnit\Framework\assertSame;

test('users can upload avatar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update-avatar'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect();

    expect(File::query()->where('files.fileable_id', $user->id)
        ->where('fileable_type', User::class)
        ->where('collection', 'avatar')
        ->exists()
    )->toBeTrue();

    $file = $user->avatarFile()->first();
    Storage::disk('public')->assertExists($file->path);
});

test('replaces the old avatar when uploading a new one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update-avatar'), [
            'avatar' => UploadedFile::fake()->image('old.jpg'),
        ]);

    $oldAvatarFile = $user->avatarFile()->first();

    $this->actingAs($user)
        ->patch(route('profile.update-avatar'), [
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ]);

    Storage::disk('public')->assertMissing($oldAvatarFile->path);
    expect(File::query()->find($oldAvatarFile->id))->toBeNull()
        ->and($user->fresh()->avatarFile)->not->toBeNull();

});

test('deletes the avatar when the user is deleted', function () {
    assertSame(1, 1);
})->skip('Implement this test once deleting users has side effects of also deleting their avatar');

test('returns 302 if the file is not an image', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update-avatar'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
        ])
        ->assertStatus(302)
        ->assertInvalid(['avatar']);
});

it('returns 302 if the file exceeds 2MB', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update-avatar'), [
            'avatar' => UploadedFile::fake()->image('large.jpg')->size(3000),
        ])
        ->assertStatus(302)
        ->assertInvalid(['avatar']);
});

test('requires authentication when uploading avatar', function () {
    $this->patch(route('profile.update-avatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertRedirect(route('login'));
});

test('deletes the avatar from the database and storage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update-avatar'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $file = $user->avatarFile()->first();
    $path = $file->path;

    $this->actingAs($user)
        ->delete(route('profile.delete-avatar'))
        ->assertRedirect();

    expect(File::find($file->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('requires authentication when deleting avatar', function () {
    $this->delete(route('profile.delete-avatar'))
        ->assertRedirect(route('login'));
});
