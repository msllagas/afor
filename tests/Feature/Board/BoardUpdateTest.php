<?php

use App\Enums\BoardListColor;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

test('users can update board name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create([
        'name' => 'Test Workspace',
    ]);

    $board = Board::factory()->for($workspace)->create([
        'name' => 'Test Board',
    ]);

    patch(route('boards.update', [
        'board' => $board,
    ]), [
        'name' => 'Updated Test Board',
    ]);

    assertDatabaseHas('boards', [
        'id'   => $board->id,
        'name' => 'Updated Test Board',
    ]);

});

test('users can change board cover color', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $board = Board::factory()->for(Workspace::factory()->forUser($user))->create();

    $response = patch(route('boards.update', [
        'board' => $board,
    ]), [
        'color' => BoardListColor::GREEN->value,
    ]);

    $response->assertRedirect();

    assertDatabaseHas('boards', [
        'id'    => $board->id,
        'color' => BoardListColor::GREEN->value,
    ]);
});

test('a board cover color must be one of the palette colors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $board = Board::factory()->for(Workspace::factory()->forUser($user))->create();

    $response = patch(route('boards.update', [
        'board' => $board,
    ]), [
        'color' => 'chartreuse',
    ]);

    $response->assertInvalid(['color']);

    expect($board->fresh()->color)->toBe(BoardListColor::NEUTRAL->value);
});
