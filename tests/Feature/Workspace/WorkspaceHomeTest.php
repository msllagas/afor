<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can access their workspace home', function () {
    $user = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->for($user)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($user)
        ->get(route('workspaces.home', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Home')
            ->has('workspace', fn (Assert $page) => $page
                ->where('id', $workspace->id)
                ->where('name', $workspace->name)
                ->where('description', $workspace->description)
                ->etc()
            )
            ->has('members', fn (Assert $page) => $page
                ->where('0.id', $member->id)
                ->where('0.name', $member->name
                ))
        );
});

test('workspace members can access their workspace home', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->for($owner)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->get(route('workspaces.home', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Home')
            ->has('workspace', fn (Assert $page) => $page
                ->where('id', $workspace->id)
                ->where('name', $workspace->name)
                ->where('description', $workspace->description)
                ->etc()
            )
            ->has('members', fn (Assert $page) => $page
                ->where('0.id', $member->id)
                ->where('0.name', $member->name
                ))
        );
});

test('user that is not a member or owner of the workspace are redirected to dashboard', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $workspace = Workspace::factory()->for($anotherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.home', [
            'workspace' => $workspace,
        ]));

    $response->assertRedirect(route('dashboard'));
});
