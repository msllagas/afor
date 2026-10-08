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

    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->create();

    // First Board List with 2 cards
    $boardList1 = BoardList::factory()->for($board)->create();
    $boardList1Card1 = Card::factory()->create(['board_list_id' => $boardList1->id, 'order' => 0]);
    $boardList1Card2 = Card::factory()->create(['board_list_id' => $boardList1->id, 'order' => 1]);

    // Second Board List with 2 cards
    $boardList2 = BoardList::factory()->for($board)->create();
    $boardList2Card1 = Card::factory()->create(['board_list_id' => $boardList2->id, 'order' => 0]);
    $boardList2Card2 = Card::factory()->create(['board_list_id' => $boardList2->id, 'order' => 1]);

    // Move boardList2Card1 to boardList1
    $payload = [
        'board_list_id' => $boardList1->id,
        'order'         => 2,
    ];

    patch(route('board-lists.cards.update', [
        'board_list' => $boardList2,
        'card'       => $boardList2Card1,
    ]), $payload);

    assertDatabaseHas('cards', [
        'id'            => $boardList2Card1->id,
        'board_list_id' => $boardList1->id,
        'order'         => 2,
    ]);

    expect($boardList1->cards()->count())->toBe(3)
        ->and($boardList2->cards()->count())->toBe(1);

});

test('users can update a card', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->create();
    $boardList = BoardList::factory()->for($board)->create();
    $card = Card::factory()->create(['board_list_id' => $boardList->id]);

    $payload = [
        'name'        => 'Updated card name',
        'description' => 'Updated card description',
    ];

    $response = patchJson(route('board-lists.cards.update', [
        'board_list' => $boardList,
        'card'       => $card,
    ]), $payload);

    $this->assertDatabaseHas('cards', [
        'id'            => $card->id,
        'board_list_id' => $boardList->id,
        'name'          => 'Updated card name',
        'description'   => 'Updated card description',
    ]);
});

test('users cannot update a card they do not own', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherUser = User::factory()->create();
    $otherUserWorkspace = Workspace::factory()->forUser($otherUser)->create();
    $otherUserBoard = Board::factory()->for($otherUserWorkspace)->create();

    $otherUserBoardList = BoardList::factory()->for($otherUserBoard)->create();

    $anotherUserBoardListCard = Card::factory()->for($otherUserBoardList)->create();

    $payload = [
        'name'        => 'Updated Board Name',
        'description' => 'Updated description',
    ];

    // Update card owned by the other user
    $response = patchJson(route('board-lists.cards.update', [
        'board_list' => $otherUserBoardList,
        'card'       => $anotherUserBoardListCard,
    ]), $payload);

    $response->assertNotFound();
    expect($anotherUserBoardListCard->refresh()->name)->not->toBe('Updated Board Name');
});

test('cards cannot be moved to a list on another board', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $boardList = BoardList::factory()->for(Board::factory()->for($workspace))->create();
    $card = Card::factory()->for($boardList)->create();
    $listOnAnotherBoard = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser()))->create();

    $response = $this->actingAs($user)->patchJson(route('board-lists.cards.update', [
        'board_list' => $boardList,
        'card'       => $card,
    ]), ['board_list_id' => $listOnAnotherBoard->id]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['board_list_id' => 'The selected board list id is invalid.']);
    expect($card->refresh()->board_list_id)->toBe($boardList->id);
});

test('a description with no text is saved as no description', function (string $description) {
    $user = User::factory()->create();
    $boardList = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($boardList)->create(['description' => '<p>Pack the tent</p>']);

    $response = $this->actingAs($user)->patch(route('board-lists.cards.update', [$boardList, $card]), [
        'description' => $description,
    ]);

    $response->assertRedirect();
    expect($card->refresh()->description)->toBeNull();
})->with([
    'an empty paragraph'    => '<p></p>',
    'a paragraph of spaces' => '<p>   </p>',
]);

test('unsafe markup in a description is removed before it is saved', function () {
    $user = User::factory()->create();
    $boardList = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($boardList)->create();

    $response = $this->actingAs($user)->patch(route('board-lists.cards.update', [$boardList, $card]), [
        'description' => '<p onclick="steal()">Bring <a href="javascript:steal()">the</a> tent</p>'
            .'<img src="x" onerror="steal()"><script>steal()</script>',
    ]);

    $response->assertRedirect();
    expect($card->refresh()->description)
        ->toContain('Bring', 'the', 'tent')
        ->not->toContain('onclick', 'javascript:', '<img', 'onerror', '<script', 'steal');
});

test('a description with only unsafe markup is saved as no description', function () {
    $user = User::factory()->create();
    $boardList = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($boardList)->create(['description' => '<p>Pack the tent</p>']);

    $response = $this->actingAs($user)->patch(route('board-lists.cards.update', [$boardList, $card]), [
        'description' => '<script>steal()</script>',
    ]);

    $response->assertRedirect();
    expect($card->refresh()->description)->toBeNull();
});

test('editor markup in a description is saved as it was written', function () {
    $user = User::factory()->create();
    $boardList = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($boardList)->create();
    $description = '<h2>Packing</h2><ul><li><p>The <strong>blue</strong> <mark class="list-blue">tent</mark></p></li></ul>';

    $response = $this->actingAs($user)->patch(route('board-lists.cards.update', [$boardList, $card]), [
        'description' => $description,
    ]);

    $response->assertRedirect();
    expect($card->refresh()->description)->toBe($description);
});

test('cards cannot be moved to a negative position', function () {
    $user = User::factory()->create();
    $boardList = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($boardList)->create(['order' => 0]);

    $response = $this->actingAs($user)->patchJson(route('board-lists.cards.update', [$boardList, $card]), ['order' => -1]);

    $response->assertInvalid(['order']);
    expect($card->refresh()->order)->toBe(0);
});
