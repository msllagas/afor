<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;

use function Pest\Laravel\assertDatabaseHas;
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

test('users are redirected when the invitation does not exist', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $workspace = Workspace::factory()->forUser($user)->create();
    $this->actingAs($anotherUser);

    $token = Str::random(32);

    $response = post(route('workspace-invitations.accept', [
        'workspace' => $workspace,
        'token'     => $token,
    ]));

})->skip(message: 'implement this test once the accept invitation route has implemented the logic');
