<?php

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;
use function PHPUnit\Framework\assertCount;

uses(RefreshDatabase::class);

test('users can move a card to another board list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // First Board List with 2 cards
    $boardList1 = BoardList::factory()->create();
    $boardList1Card1 = Card::factory()->create(['board_list_id' => $boardList1->id, 'order' => 0]);
    $boardList1Card2 = Card::factory()->create(['board_list_id' => $boardList1->id, 'order' => 1]);

    // Second Board List with 2 cards
    $boardList2 = BoardList::factory()->create();
    $boardList2Card1 = Card::factory()->create(['board_list_id' => $boardList2->id, 'order' => 0]);
    $boardList2Card2 = Card::factory()->create(['board_list_id' => $boardList2->id, 'order' => 1]);

    // Move boardList2Card1 to boardList1
    $payload = [
        'board_list_id' => $boardList1->id,
        'order' => 2,
    ];

    $response = patch(route('board-lists.cards.update', [
        'board_list' => $boardList2,
        'card' => $boardList2Card1,
    ]), $payload);

    assertDatabaseHas('cards', [
        'id' => $boardList2Card1->id,
        'board_list_id' => $boardList1->id,
        'order' => 2,
    ]);

    assertCount(3, $boardList1->cards);
    assertCount(1, $boardList2->cards);

});
