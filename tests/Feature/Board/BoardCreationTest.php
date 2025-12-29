<?php

use App\Events\BoardAddedToWorkspace;
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
        'name' => 'Test Board',
    ]);
});

test('board creation dispatches an event', function () {
    Event::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->post(route('workspaces.boards.store', [
            'workspace' => $workspace,
        ]), [
            'name' => 'Test Board',
        ]);

    Event::assertDispatched(BoardAddedToWorkspace::class);
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
