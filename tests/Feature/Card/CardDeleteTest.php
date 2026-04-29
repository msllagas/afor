<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

use function PHPUnit\Framework\assertEquals;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();

});

test('workspace owners can delete their board list cards', function () {
    $board = Board::factory()->for($this->workspace)->create();
    $boardList = BoardList::factory()->for($board)->create();
    $card = Card::factory()->for($boardList)->create();

    $response = $this->actingAs($this->user)
        ->delete(route('board-lists.cards.destroy', [
            'board_list' => $boardList,
            'card'       => $card,
        ]));

    $this->assertSoftDeleted('cards', [
        'id'            => $card->id,
        'board_list_id' => $boardList->id,
    ]);
    $response->assertRedirect();

});

test('workspace members can delete the board list cards', function () {
    $member = User::factory()->create();

    $this->workspace->users()->attach($member);
    $board = Board::factory()->for($this->workspace)->create();
    $boardList = BoardList::factory()->for($board)->create();
    $card = Card::factory()->for($boardList)->create();

    $response = $this->actingAs($member)
        ->delete(route('board-lists.cards.destroy', [
            'board_list' => $boardList,
            'card'       => $card,
        ]));

    $this->assertSoftDeleted('cards', [
        'id'            => $card->id,
        'board_list_id' => $boardList->id,
    ]);
    $response->assertRedirect();

});

test('non-members cannot delete a card in a workspace they do not belong', function () {
    assertEquals(1, 1);
})->skip('Implement this test once card destroy route has implemented the logic');
