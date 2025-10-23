<?php


use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use App\Models\Workspace;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

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

test('adding a new board list assigns next order number', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $workspace = Workspace::factory()->create();
    $board = Board::factory()->for($workspace)->create();

    BoardList::factory()->for($board)->create(
        ['order' => 0]
    );

    BoardList::factory()->for($board)->create(
        ['order' => 1]
    );


    $response = post(route('boards.board-lists.store', [
        'board' => $board,
    ]), [
        'name' => 'Test Board List',
    ]);

    $response->assertRedirect();

    assertDatabaseHas('board_lists', [
        'name' => 'Test Board List',
        'board_id' => $board->id,
        'order' => 2,
    ]);
});
