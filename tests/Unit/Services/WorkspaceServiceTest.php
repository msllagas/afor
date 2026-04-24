<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;

use function Pest\Laravel\assertDatabaseHas;

test('service generates invitation link for a workspace per user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();

    $service = app(WorkspaceService::class);

    $link = $service->generateInvitationLink($workspace, $user);

    expect($link)
        ->toBeString()
        ->toContain("/invite/{$workspace->id}/");

    assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $workspace->id,
        'invited_by' => $user->id,
        'token' => Str::of($link)->afterLast('/'),
    ]);
});

test('service removes member from the workspace', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member->id);

    $service = app(WorkspaceService::class);
    $service->removeMember($workspace, $member);

    $this->assertDatabaseMissing('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);
});

test('service throws InvalidArgumentException when trying to remove the workspace owner', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();
    $workspace->users()->attach($user->id); // attach the owner

    $service = app(WorkspaceService::class);

    $service->removeMember($workspace, $user);
})->throws(InvalidArgumentException::class, 'Cannot remove the workspace owner.');

test('service throws InvalidArgumentException when trying to remove a non-member', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();
    $nonMember = User::factory()->create();

    $service = app(WorkspaceService::class);

    $service->removeMember($workspace, $nonMember);
})->throws(InvalidArgumentException::class, 'User is not a member of this workspace.');
