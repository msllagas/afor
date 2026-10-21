<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

test('users can create board', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->post(route('workspaces.boards.store', [
            'workspace' => $workspace,
        ]), [
            'name' => 'Test Board',
        ]);

    $this->assertDatabaseHas('boards', [
        'name'  => 'Test Board',
        'color' => 'neutral',
    ]);
});

test('users are redirected to boards.show after creating board', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $response = $this->actingAs($user)
        ->post(route('workspaces.boards.store', [
            'workspace' => $workspace,
        ]), [
            'name' => 'Test Board',
        ]);

    $board = Board::query()->first();

    $response->assertRedirect(route('boards.show', [
        'board' => $board,
    ]));
});

test('workspace members cannot create boards in it', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member);

    $response = $this->actingAs($member)
        ->post(route('workspaces.boards.store', $workspace), ['name' => 'Test Board']);

    $response->assertForbidden();
    $this->assertDatabaseMissing('boards', ['name' => 'Test Board']);
});

test('users outside the workspace get not found when creating a board in it', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $response = $this->actingAs(User::factory()->create())
        ->post(route('workspaces.boards.store', $workspace), ['name' => 'Test Board']);

    $response->assertNotFound();
    $this->assertDatabaseMissing('boards', ['name' => 'Test Board']);
});

test('users can create the last board the workspace limit allows', function () {
    config(['limits.boards_per_workspace' => 2]);
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Board::factory()->for($workspace)->create();

    $response = $this->actingAs($user)
        ->post(route('workspaces.boards.store', $workspace), ['name' => 'Last one']);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('boards', ['name' => 'Last one']);
});

test('users cannot create a board once the workspace reaches its limit, archived boards included', function () {
    config(['limits.boards_per_workspace' => 2]);
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Board::factory()->for($workspace)->create();
    Board::factory()->for($workspace)->archived()->create();

    $response = $this->actingAs($user)
        ->post(route('workspaces.boards.store', $workspace), ['name' => 'One too many']);

    $response->assertSessionHasErrors([
        'name' => 'This workspace has reached its limit of 2 boards. Delete archived boards to make room.',
    ]);
    $this->assertDatabaseMissing('boards', ['name' => 'One too many']);
});
