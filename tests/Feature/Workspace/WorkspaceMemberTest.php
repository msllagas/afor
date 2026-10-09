<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can access their workspace members', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Member')
            ->has('workspace')
            ->has('owner', fn (Assert $page) => $page
                ->where('id', $user->id)
                ->where('name', $user->name)
                ->missing('email')
                ->where('avatar', $user->avatar)
            )
            ->has('members')
            ->where('canManageMembers', true)
            ->where('canInvite', true)
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('inviteLink')
            )
        );
});

test('workspace members can access their workspace members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Member')
            ->has('workspace')
            ->has('owner', fn (Assert $page) => $page
                ->where('id', $owner->id)
                ->where('name', $owner->name)
                ->missing('email')
                ->where('avatar', $owner->avatar)
            )
            ->has('members', 1, fn (Assert $page) => $page
                ->where('id', $member->id)
                ->where('name', $member->name)
                ->missing('email')
                ->where('avatar', $member->avatar)
                ->has('joined_at')
            )
            ->where('canManageMembers', false)
        );
});

test('workspace members do not get an invite link, even when they ask for it', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member->id);

    $this->actingAs($member)
        ->get(route('workspaces.members', ['workspace' => $workspace]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canInvite', false)
            ->missing('inviteLink')
            ->reload(fn (Assert $reload) => $reload->missing('inviteLink'), only: 'inviteLink')
        );

    $this->assertDatabaseMissing('workspace_invitations', ['workspace_id' => $workspace->id]);
});

test('users outside the workspace get not found for its members', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $workspace = Workspace::factory()->forUser($anotherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertNotFound();
});

test('workspace members are listed alphabetically', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach([
        User::factory()->create(['name' => 'Zara Cruz'])->id,
        User::factory()->create(['name' => 'Ana Reyes'])->id,
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.members', ['workspace' => $workspace]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.0.name', 'Ana Reyes')
            ->where('members.1.name', 'Zara Cruz')
        );
});
