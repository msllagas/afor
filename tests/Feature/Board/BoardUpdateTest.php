<?php

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
