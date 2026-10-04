<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceService;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

test('users can accept invitation from another users', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $workspace = Workspace::factory()->forUser($user)->create();
    $this->actingAs($anotherUser);

    $service = app(WorkspaceService::class);
    $link = $service->generateInvitationLink($workspace, $user);
    $token = Str::of($link)->afterLast('/')->value();

    $response = post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $token,
    ]));

    $response->assertRedirect(route('workspaces.home', [
        'workspace' => $workspace,
    ], absolute: false));

    assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $anotherUser->id,
    ]);

});

test('users cannot accept their own invitation', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $this->actingAs($user);

    $service = app(WorkspaceService::class);
    $link = $service->generateInvitationLink($workspace, $user);
    $token = Str::of($link)->afterLast('/')->value();

    $response = post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $token,
    ]));

    // Just redirect to workspace home
    $response->assertRedirect(route('workspaces.home', [
        'workspace' => $workspace,
    ]));
});

test('users who already joined the workspace are redirected', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $workspace = Workspace::factory()->forUser($user)->create();
    $workspace->users()->attach($anotherUser);
    $this->actingAs($anotherUser);

    $service = app(WorkspaceService::class);
    $link = $service->generateInvitationLink($workspace, $user);
    $token = Str::of($link)->afterLast('/')->value();

    $response = post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $token,
    ]));

    // Just redirect to workspace home
    $response->assertRedirect(route('workspaces.home', [
        'workspace' => $workspace,
    ]));
});

test('users get not found when the invitation does not exist', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => Str::random(32),
    ]));

    $response->assertNotFound();
    assertDatabaseMissing('workspace_user', ['user_id' => $user->id]);
});

test('users cannot join through an invite link a member created', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member->id);
    $memberInvitation = WorkspaceInvitation::factory()->for($workspace)->issuedBy($member)->create();
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $memberInvitation->token,
    ]));

    $response->assertNotFound();
    assertDatabaseMissing('workspace_user', ['user_id' => $outsider->id]);
});

test('users cannot join a workspace with an invite token from another workspace', function () {
    $otherInvitation = WorkspaceInvitation::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $otherInvitation->token,
    ]));

    $response->assertNotFound();
    assertDatabaseMissing('workspace_user', ['user_id' => $user->id]);
});

test('guests are sent to log in before accepting an invitation', function () {
    $invitation = WorkspaceInvitation::factory()->create();

    $response = post(route('workspace-invitations.accept', [
        'workspace' => $invitation->workspace_id,
        'token'     => $invitation->token,
    ]));

    $response->assertRedirect(route('login'));
});
