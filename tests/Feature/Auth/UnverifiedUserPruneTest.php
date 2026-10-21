<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;

test('accounts that never verify their email are removed with their files after the grace period', function () {
    Storage::fake('public');
    config(['limits.unverified_user_retention_days' => 7]);

    $this->travel(-8)->days();
    $abandoned = User::factory()->unverified()->create();
    $workspace = Workspace::factory()->forUser($abandoned)->create();
    $this->actingAs($abandoned)->patch(route('profile.update-avatar'), ['avatar' => UploadedFile::fake()->image('a.jpg')]);
    $avatarPath = $abandoned->avatarFile()->value('path');
    $verified = User::factory()->create();
    $this->travelBack();
    $recent = User::factory()->unverified()->create();

    $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

    expect(User::query()->pluck('id')->all())->toEqualCanonicalizing([$verified->id, $recent->id]);
    $this->assertModelMissing($workspace);
    Storage::disk('public')->assertMissing($avatarPath);
});
