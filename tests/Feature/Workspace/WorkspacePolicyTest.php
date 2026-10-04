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

test('only the workspace creator can manage members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member);

    expect($owner->can('manageMembers', $workspace))->toBeTrue()
        ->and($member->can('manageMembers', $workspace))->toBeFalse()
        ->and(User::factory()->create()->can('manageMembers', $workspace))->toBeFalse();
});

test('only the workspace creator can update, delete or invite people to the workspace', function (string $ability) {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member);

    expect($owner->can($ability, $workspace))->toBeTrue()
        ->and($member->can($ability, $workspace))->toBeFalse()
        ->and(User::factory()->create()->can($ability, $workspace))->toBeFalse();
})->with(['update', 'delete', 'invite']);

test('only workspace members can leave the workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member);

    expect($member->can('leave', $workspace))->toBeTrue()
        ->and($owner->can('leave', $workspace))->toBeFalse()
        ->and(User::factory()->create()->can('leave', $workspace))->toBeFalse();
});
