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
        'color' => BoardListColor::ANGEL->value,
    ]);

    $response->assertRedirect();

    assertDatabaseHas('board_lists', [
        'id' => $boardList->id,
        'color' => BoardListColor::ANGEL->value,
    ]);
});

test('users can change board list name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $board = Board::factory()->create();
    $boardList = BoardList::factory()->for($board)->create(['name' => 'Initial name']);

    $response = patch(route('boards.board-lists.update', [
        'board' => $board,
        'board_list' => $boardList,
    ]), [
        'name' => 'New name',
    ]);

    $response->assertRedirect();

    assertDatabaseHas('board_lists', [
        'id' => $boardList->id,
        'name' => 'New name',
    ]);
});
