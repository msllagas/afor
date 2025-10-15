<?php

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

uses(RefreshDatabase::class);

test('users can reorder cards in board list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $boardList = BoardList::factory()->create();
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
        'id' => $card1->id,
        'order' => 1,
    ]);

    assertDatabaseHas('cards', [
        'id' => $card2->id,
        'order' => 2,
    ]);

    assertDatabaseHas('cards', [
        'id' => $card3->id,
        'order' => 0,
    ]);
});

