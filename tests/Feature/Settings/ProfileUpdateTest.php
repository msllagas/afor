<?php

use App\Models\File;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name'  => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('the profile page lists the workspaces deleted with the account and who loses them', function () {
    $user = User::factory()->create();
    $sharedWorkspace = Workspace::factory()->forUser($user)->create(['name' => 'Beach trip']);
    $sharedWorkspace->users()->attach(User::factory()->count(2)->create());
    $privateWorkspace = Workspace::factory()->forUser($user)->create(['name' => 'Errands']);
    Workspace::factory()->forUser()->create()->users()->attach($user);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('ownedWorkspacesToDelete', [
            ['id' => $sharedWorkspace->id, 'name' => 'Beach trip', 'members_count' => 2],
            ['id' => $privateWorkspace->id, 'name' => 'Errands', 'members_count' => 0],
        ])
    );
});

test('deleting an account removes the logos of the workspaces it owns but not of workspaces it was a member of', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $ownedWorkspace = Workspace::factory()->forUser($user)->create();
    $otherOwner = User::factory()->create();
    $memberWorkspace = Workspace::factory()->forUser($otherOwner)->create();
    $memberWorkspace->users()->attach($user);
    $this->actingAs($user)->patch(route('workspaces.update', $ownedWorkspace), ['logo' => UploadedFile::fake()->image('owned.png')]);
    $this->actingAs($otherOwner)->patch(route('workspaces.update', $memberWorkspace), ['logo' => UploadedFile::fake()->image('kept.png')]);
    $ownedLogo = $ownedWorkspace->logoFile()->firstOrFail();
    $keptLogo = $memberWorkspace->logoFile()->firstOrFail();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertModelMissing($ownedWorkspace);
    $this->assertModelMissing($ownedLogo);
    Storage::disk('public')->assertMissing($ownedLogo->path);
    $this->assertModelExists($memberWorkspace);
    $this->assertModelExists($keptLogo);
    Storage::disk('public')->assertExists($keptLogo->path);
    expect(File::query()->count())->toBe(1);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
