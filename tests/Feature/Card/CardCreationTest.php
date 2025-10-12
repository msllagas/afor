<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

test('users can create cards in board lists', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $board = Board::factory()->create([
        'name' => 'Test Board',
        'user_id' => $user->id,
    ]);

    Artisan::call('board:seed',
        [
            'board' => $board->id,
        ]
    );

    $boardList = $board->boardLists()->firstOrFail();

    $response = post(route('board-lists.cards.store', [
        'board_list' => $boardList,
    ]), [
        'name' => 'Test Card',
    ]);

    assertDatabaseHas('cards', [
        'name' => 'Test Card',
        'board_list_id' => $boardList->id,
    ]);
});

test('adding a new card assigns next order number', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $boardList = BoardList::factory()->create();

    Card::factory()->create(['board_list_id' => $boardList->id, 'order' => 1]);
    Card::factory()->create(['board_list_id' => $boardList->id, 'order' => 2]);

    $response = post(route('board-lists.cards.store', [
        'board_list' => $boardList,
    ]), [
        'name' => 'Test Card',
    ]);

    $response->assertRedirect();

    assertDatabaseHas('cards', [
        'name' => 'Test Card',
        'board_list_id' => $boardList->id,
        'order' => 3,
    ]);
});
