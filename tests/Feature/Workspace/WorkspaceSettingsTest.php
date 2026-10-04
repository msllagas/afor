<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can open the workspace settings', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();

    $response = $this->actingAs($owner)
        ->get(route('workspaces.settings', $workspace));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Settings')
            ->has('workspace', fn (Assert $page) => $page
                ->where('id', $workspace->id)
                ->where('name', $workspace->name)
                ->where('description', $workspace->description)
                ->etc()
            )
        );
});

test('workspace members are redirected to the dashboard from the workspace settings', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member);

    $this->actingAs($member)
        ->get(route('workspaces.settings', $workspace))
        ->assertRedirect(route('dashboard'));
});

test('users outside the workspace are redirected to the dashboard from the workspace settings', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('workspaces.settings', $workspace))
        ->assertRedirect(route('dashboard'));
});

test('guests cannot open the workspace settings', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $this->get(route('workspaces.settings', $workspace))
        ->assertRedirect(route('login'));
});
