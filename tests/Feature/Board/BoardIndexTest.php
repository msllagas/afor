<?php

use App\Models\Board;
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
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Index')
            ->has('ownedWorkspaces', 1, fn (Assert $page) => $page
                ->where('id', $ownedWorkspace->id)
                ->where('name', 'Owned Workspace')
                ->has('boards')
                ->etc()
            )
            ->has('sharedWorkspaces', 1, fn (Assert $page) => $page
                ->where('id', $sharedWorkspace->id)
                ->where('name', 'Shared Workspace')
                ->has('boards')
                ->etc()
            )
        );
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('boards.index'));

    $response->assertRedirect(route('login'));
});

test('boards index excludes workspaces the user does not belong to', function () {
    $user = User::factory()->create();
    Workspace::factory()->forUser(User::factory()->create())->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces', 0)
        ->has('sharedWorkspaces', 0)
    );
});

test('boards index lists every shared workspace as a list', function () {
    $user = User::factory()->create();
    Workspace::factory()->forUser($user)->create();

    $sharedWorkspaces = Workspace::factory()
        ->forUser(User::factory()->create())
        ->count(2)
        ->create();
    $user->sharedWorkspaces()->attach($sharedWorkspaces);

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces', 1)
        ->has('sharedWorkspaces', 2)
        ->where('sharedWorkspaces', fn ($workspaces) => array_is_list($workspaces->all()))
    );
});

test('boards index hides archived boards', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $activeBoard = Board::factory()->for($workspace)->create();
    Board::factory()->for($workspace)->archived($user)->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces.0.boards', 1)
        ->where('ownedWorkspaces.0.boards.0.id', $activeBoard->id)
    );
});

test('boards index marks the boards the user starred', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $starredBoard = Board::factory()->for($workspace)->create(['created_at' => now()->subDay()]);
    $otherBoard = Board::factory()->for($workspace)->create();
    $user->favoriteBoards()->attach($starredBoard);
    User::factory()->create()->favoriteBoards()->attach($otherBoard);

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('ownedWorkspaces.0.boards.0.id', $starredBoard->id)
        ->where('ownedWorkspaces.0.boards.0.is_favorited', true)
        ->where('ownedWorkspaces.0.boards.1.is_favorited', false)
    );
});
