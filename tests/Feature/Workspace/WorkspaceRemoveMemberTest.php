<?php

use App\Models\User;
use App\Models\Workspace;

test('workspace creator can remove members', function () {
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

test('workspace creator cannot remove themselves from the workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $workspace->users()->attach($user->id);

    $response = $this->actingAs($user)
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $user,
        ]));

    $response->assertStatus(403);
    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $user->id,
    ]);
});

test('workspace creator cannot remove non-member', function () {
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

test('workspace members cannot remove other members', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    $workspace->users()->attach([$member->id, $otherMember->id]);

    $this->actingAs($member)
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $otherMember,
        ]))
        ->assertForbidden();

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $otherMember->id,
    ]);
});

test('workspace members cannot remove themselves', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member->id);

    $this->actingAs($member)
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $member,
        ]))
        ->assertForbidden();

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $member->id,
    ]);
});

test('users outside the workspace cannot remove its members', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member->id);

    $this->actingAs(User::factory()->create())
        ->delete(route('workspaces.members.user.destroy', [
            'workspace' => $workspace,
            'user'      => $member,
        ]))
        ->assertNotFound();

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $member->id,
    ]);
});
