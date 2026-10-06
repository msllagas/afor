<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('starred boards are shared with every page in name order', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $roadmap = Board::factory()->for($workspace)->create(['name' => 'Roadmap']);
    $bugs = Board::factory()->for($workspace)->create(['name' => 'Bugs']);
    Board::factory()->for($workspace)->create(['name' => 'Not starred']);
    $user->favoriteBoards()->attach([$roadmap->id, $bugs->id]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('starredBoards', [
            ['id' => $bugs->id, 'name' => 'Bugs', 'workspace_id' => $workspace->id],
            ['id' => $roadmap->id, 'name' => 'Roadmap', 'workspace_id' => $workspace->id],
        ])
    );
});

test('starred boards include boards the user was added to in shared workspaces', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user);
    $board = Board::factory()->for($workspace)->withMembers($user)->create();
    $user->favoriteBoards()->attach($board);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('starredBoards', 1)
        ->where('starredBoards.0.id', $board->id)
    );
});

test('starred boards leave out archived boards', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->archived($user)->create();
    $user->favoriteBoards()->attach($board);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page->has('starredBoards', 0));
});

test('starred boards leave out boards from workspaces the user no longer belongs to', function () {
    $user = User::factory()->create();
    Workspace::factory()->forUser($user)->create();
    $formerWorkspace = Workspace::factory()->create();
    $board = Board::factory()->for($formerWorkspace)->create();
    $user->favoriteBoards()->attach($board);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page->has('starredBoards', 0));
});

test('starred boards leave out boards the user is not on', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user);
    $board = Board::factory()->for($workspace)->create();
    $user->favoriteBoards()->attach($board);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page->has('starredBoards', 0));
});
