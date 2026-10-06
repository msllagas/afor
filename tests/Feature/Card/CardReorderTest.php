<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

test('users can reorder cards in board list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->create();

    $boardList = BoardList::factory()->for($board)->create();
    $card1 = Card::factory()->create(['board_list_id' => $boardList->id, 'order' => 0]);
    $card2 = Card::factory()->create(['board_list_id' => $boardList->id, 'order' => 1]);
    $card3 = Card::factory()->create(['board_list_id' => $boardList->id, 'order' => 2]);

    $payload = [
        'cards' => [
            ['id' => $card1->id, 'order' => 1],
            ['id' => $card2->id, 'order' => 2],
            ['id' => $card3->id, 'order' => 0],
        ],
    ];

    $response = patch(route('board-lists.cards.reorder', [
        'board_list' => $boardList,
    ]), $payload);

    assertDatabaseHas('cards', [
        'id'    => $card1->id,
        'order' => 1,
    ]);

    assertDatabaseHas('cards', [
        'id'    => $card2->id,
        'order' => 2,
    ]);

    assertDatabaseHas('cards', [
        'id'    => $card3->id,
        'order' => 0,
    ]);
});

test('card reorders must be a list of card ids and positions', function (array $payload, string $invalidField) {
    $user = User::factory()->create();
    $boardList = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($boardList)->create(['order' => 0]);

    $this->actingAs($user)
        ->patch(route('board-lists.cards.reorder', $boardList), $payload)
        ->assertInvalid([$invalidField]);

    expect($card->refresh()->order)->toBe(0);
})->with([
    'no cards'                => [[], 'cards'],
    'cards that are text'     => [['cards' => 'first'], 'cards'],
    'an id that is no id'     => [['cards' => [['id' => 'not-an-id', 'order' => 1]]], 'cards.0.id'],
    'a negative position'     => [['cards' => [['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => -1]]], 'cards.0.order'],
    'a position that is text' => [['cards' => [['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => 'first']]], 'cards.0.order'],
    'the same card twice'     => [['cards' => [
        ['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => 0],
        ['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => 1],
    ]], 'cards.0.id'],
]);
