<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can access their workspace members', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('workspaces/Member')
            ->has('workspace')
            ->has('owner', fn(Assert $page) => $page
                ->where('id', $user->id)
                ->where('name', $user->name
                ))
            ->has('members')
            ->loadDeferredProps(fn(Assert $reload) => $reload
                ->has('inviteLink')
            )
        );
});

test('workspace members can access their workspace members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->for($owner)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('workspaces/Member')
            ->has('workspace')
            ->has('owner', fn(Assert $page) => $page
                ->where('id', $owner->id)
                ->where('name', $owner->name
                ))
            ->has('members', fn(Assert $page) => $page
                ->where('0.id', $member->id)
                ->where('0.name', $member->name
                ))
            ->loadDeferredProps(fn(Assert $reload) => $reload
                ->has('inviteLink')
            )
        );
});

test('user that is not a member or owner of the workspace are redirected to dashboard', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $workspace = Workspace::factory()->for($anotherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertRedirect(route('dashboard'));
});

