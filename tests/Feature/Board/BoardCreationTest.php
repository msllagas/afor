<?php

use App\Models\Board;
use App\Models\User;

test('users can create board', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('boards.store'), [
        'name' => 'Test Board',
    ]);

    $this->assertDatabaseHas('boards', [
        'name' => 'Test Board',
    ]);
});

test('users are redirected to boards.show after creating board', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('boards.store'), [
        'name' => 'Test Board',
    ]);

    $board = Board::query()->first();

    $response->assertRedirect(route('boards.show', [
        'board' => $board,
    ]));
});
