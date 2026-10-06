<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can open the workspace settings', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach(User::factory()->count(2)->create());
    Board::factory()->for($workspace)->count(3)->create();

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
            ->where('boardCount', 3)
            ->where('memberCount', 2)
        );
});

test('workspace members are forbidden from the workspace settings', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member);

    $this->actingAs($member)
        ->get(route('workspaces.settings', $workspace))
        ->assertForbidden();
});

test('users outside the workspace get not found for the workspace settings', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('workspaces.settings', $workspace))
        ->assertNotFound();
});

test('guests cannot open the workspace settings', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $this->get(route('workspaces.settings', $workspace))
        ->assertRedirect(route('login'));
});
