<?php

use App\Enums\BoardListColor;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

test('users can archive a board list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $board = Board::factory()->create();
    $boardList = BoardList::factory()->for($board)->create();

    expect($boardList->is_archived)->tobeFalse();

    $response = patch(route('boards.board-lists.update', [
        'board' => $board,
        'board_list' => $boardList,
    ]), [
        'is_archived' => true,
    ]);

    $response->assertRedirect();

    assertDatabaseHas('board_lists', [
        'id' => $boardList->id,
        'is_archived' => true,
    ]);
});

test('users can change board list color', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $board = Board::factory()->create();
    $boardList = BoardList::factory()->for($board)->create();

    $response = patch(route('boards.board-lists.update', [
        'board' => $board,
        'board_list' => $boardList,
    ]), [
        'color' => BoardListColor::PINK->value,
    ]);

    $response->assertRedirect();

    assertDatabaseHas('board_lists', [
        'id' => $boardList->id,
        'color' => BoardListColor::PINK->value,
    ]);
});
