<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;
use function Pest\Laravel\patchJson;

test('users can move a card to another board list on the same board', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->for($user)->create();
    $board = Board::factory()->for($workspace)->create();

    //  Artisan call to seed the board with board lists
    Artisan::call('board:seed', [
        'board' => $board->id,
    ]);

    $boardLists = $board->boardLists()->take(2)->get();

    // First Board List with 2 cards
    $boardList1 = $boardLists[0];
    $boardList1Card1 = Card::factory()->create(['board_list_id' => $boardList1->id, 'order' => 0]);
    $boardList1Card2 = Card::factory()->create(['board_list_id' => $boardList1->id, 'order' => 1]);

    // Second Board List with 2 cards
    $boardList2 = $boardLists[1];
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

    expect($boardList1->cards()->count())->toBe(3)
        ->and($boardList2->cards()->count())->toBe(1);

});

test('users can update a card', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $workspace = Workspace::factory()->for($user)->create();
    $board = Board::factory()->for($workspace)->create();
    $boardList = BoardList::factory()->for($board)->create();
    $card = Card::factory()->create(['board_list_id' => $boardList->id]);

    $payload = [
        'name' => 'Updated card name',
        'description' => 'Updated card description',
    ];

    $response = patchJson(route('board-lists.cards.update', [
        'board_list' => $boardList,
        'card' => $card,
    ]), $payload);

    $this->assertDatabaseHas('cards', [
        'id' => $card->id,
        'board_list_id' => $boardList->id,
        'name' => 'Updated card name',
        'description' => 'Updated card description',
    ]);
});

test('users cannot update a card they do not own', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherUser = User::factory()->create();
    $otherUserWorkspace = Workspace::factory()->for($otherUser)->create();
    $otherUserBoard = Board::factory()->for($otherUserWorkspace)->create();

    $otherUserBoardList = BoardList::factory()->for($otherUserBoard)->create();

    $anotherUserBoardListCard = Card::factory()->for($otherUserBoardList)->create();

    $payload = [
        'name' => 'Updated Board Name',
        'description' => 'Updated description',
    ];

    // Update card owned by the other user
    $response = patchJson(route('board-lists.cards.update', [
        'board_list' => $otherUserBoard->boardLists()->first()->id,
        'card' => $anotherUserBoardListCard,
    ]), $payload);

    $response->assertForbidden()
        ->assertJson([
            'message' => 'You do not own this card.',
        ]);
})->skip();
