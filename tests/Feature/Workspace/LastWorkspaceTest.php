<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('opening a workspace page remembers it as the last workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $this->actingAs($user)->get(route('workspaces.members', $workspace));

    expect($user->fresh()->last_workspace_id)->toBe($workspace->id);
});

test('opening a board remembers its workspace as the last workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member);
    $board = Board::factory()->for($workspace)->withMembers($member)->create();

    $this->actingAs($member)->get(route('boards.show', $board));

    expect($member->fresh()->last_workspace_id)->toBe($workspace->id);
});

test('opening a workspace the user cannot access does not change the last workspace', function () {
    $user = User::factory()->create();
    $ownWorkspace = Workspace::factory()->forUser($user)->create();
    $otherWorkspace = Workspace::factory()->create();
    $user->forceFill(['last_workspace_id' => $ownWorkspace->id])->save();

    $response = $this->actingAs($user)->get(route('workspaces.home', $otherWorkspace));

    $response->assertRedirect(route('dashboard'));
    expect($user->fresh()->last_workspace_id)->toBe($ownWorkspace->id);
});

test('the current workspace is the one in the url', function () {
    $user = User::factory()->create();
    $lastWorkspace = Workspace::factory()->forUser($user)->create();
    $openedWorkspace = Workspace::factory()->forUser($user)->create();
    $user->forceFill(['last_workspace_id' => $lastWorkspace->id])->save();

    $response = $this->actingAs($user)->get(route('workspaces.home', $openedWorkspace));

    $response->assertInertia(fn (Assert $page) => $page->where('currentWorkspaceId', $openedWorkspace->id));
});

test('pages outside a workspace use the last workspace as the current workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $user->forceFill(['last_workspace_id' => $workspace->id])->save();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page->where('currentWorkspaceId', $workspace->id));
});

test('deleting the last workspace clears it from the user', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $user->forceFill(['last_workspace_id' => $workspace->id])->save();

    $this->actingAs($user)->delete(route('workspaces.destroy', $workspace));

    expect($user->fresh()->last_workspace_id)->toBeNull();
});
