<?php

use App\Models\User;
use App\Models\Workspace;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('users are taken to the workspace they last opened', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $user->forceFill(['last_workspace_id' => $workspace->id])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.home', $workspace));
});

test('users who have not opened a workspace yet are taken to their boards', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('boards.index'));
});

test('users whose last workspace is out of reach are taken to their boards', function () {
    $user = User::factory()->create();
    $otherWorkspace = Workspace::factory()->forUser()->create();
    $user->forceFill(['last_workspace_id' => $otherWorkspace->id])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('boards.index'));
});
