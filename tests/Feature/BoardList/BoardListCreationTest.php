<?php


use App\Models\Board;
use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;

test('users can create board list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $board = Board::factory()->create();

    $response = $this->post(route('boards.board-lists.store', [
        'board' => $board,
    ]), [
        'name' => 'Test Board List',
    ]);

    assertDatabaseHas('board_lists', [
        'name' => 'Test Board List',
        'board_id' => $board->id,
    ]);
});
