<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;

test('workspace owners can reset the invite link', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $invitation = WorkspaceInvitation::factory()->for($workspace)->create();

    $response = $this->actingAs($owner)
        ->from(route('workspaces.members', ['workspace' => $workspace]))
        ->post(route('workspaces.invite-link.reset', ['workspace' => $workspace]));

    $response->assertRedirect(route('workspaces.members', ['workspace' => $workspace]));
    $this->assertModelMissing($invitation);
    $this->assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $workspace->id,
        'invited_by'   => $owner->id,
    ]);
});

test('a link a member forwarded stops working once the owner resets it', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member->id);
    $forwardedInvitation = WorkspaceInvitation::factory()->for($workspace)->create();
    $outsider = User::factory()->create();

    $this->actingAs($owner)->post(route('workspaces.invite-link.reset', ['workspace' => $workspace]));

    $response = $this->actingAs($outsider)->post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $forwardedInvitation->token,
    ]));

    $response->assertNotFound();
    $this->assertDatabaseMissing('workspace_user', ['user_id' => $outsider->id]);
});

test('workspace members cannot reset the invite link', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member->id);
    $invitation = WorkspaceInvitation::factory()->for($workspace)->create();

    $response = $this->actingAs($member)
        ->post(route('workspaces.invite-link.reset', ['workspace' => $workspace]));

    $response->assertForbidden();
    $this->assertModelExists($invitation);
});

test('users outside the workspace get not found when resetting its invite link', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $invitation = WorkspaceInvitation::factory()->for($workspace)->create();

    $response = $this->actingAs(User::factory()->create())
        ->post(route('workspaces.invite-link.reset', ['workspace' => $workspace]));

    $response->assertNotFound();
    $this->assertModelExists($invitation);
});
