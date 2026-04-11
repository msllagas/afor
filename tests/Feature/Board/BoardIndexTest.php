<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('board screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertStatus(200);
});

test('users can view boards index with their own and shared workspaces', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $ownedWorkspace = Workspace::factory()->forUser($user)->create(['name' => 'Owned Workspace']);

    $sharedWorkspace = Workspace::factory()->forUser($anotherUser)->create(['name' => 'Shared Workspace']);
    $sharedWorkspace->users()->attach($user);

    $response = $this->actingAs($user)
        ->get(route('boards.index'));


    $response->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('boards/Index')
            ->has('ownedWorkspaces', 1, fn(Assert $page) => $page
                ->where('id', $ownedWorkspace->id)
                ->where('name', 'Owned Workspace')
                ->has('boards')
            )
            ->has('sharedWorkspaces', 1, fn(Assert $page) => $page
                ->where('id', $sharedWorkspace->id)
                ->where('name', 'Shared Workspace')
                ->has('boards')
                ->has('shared_workspaces') // Renamed pivot property to shared_workspaces
            )
        );
});
