<?php

use App\Models\User;
use App\Models\Workspace;

test('workspace owners and members can view the workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member);

    expect($owner->can('view', $workspace))->toBeTrue()
        ->and($member->can('view', $workspace))->toBeTrue();
});

test('users outside the workspace cannot view it', function () {
    $workspace = Workspace::factory()->forUser()->create();

    expect(User::factory()->create()->can('view', $workspace))->toBeFalse();
});
