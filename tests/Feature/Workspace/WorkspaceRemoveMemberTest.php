<?php

use App\Models\User;
use App\Models\Workspace;

test('workspace owner can remove members', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $member = User::factory()->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($user)
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $member,
        ]));

    $response->assertStatus(302);
    $this->assertDatabaseMissing('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $member->id,
    ]);
});

test('workspace owner cannot remove themselves from the workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $workspace->users()->attach($user->id);

    $response = $this->actingAs($user)
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $user,
        ]));

    $response->assertStatus(403);
});

test('workspace owner cannot remove non-member', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $nonMember = User::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $nonMember,
        ]));

    $response->assertStatus(404); // It is 404 due to scopeBindings method on route
});
