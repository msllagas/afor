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
        'name' => 'Test Board',
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
