<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
    $this->boardList = BoardList::factory()->for($this->board)->create(['archived_at' => now()]);
});

test('the workspace owner can permanently delete an archived list along with its cards', function () {
    $card = Card::factory()->for($this->boardList)->create();
    $deletedCard = Card::factory()->for($this->boardList)->create(['deleted_at' => now()]);

    $response = $this->actingAs($this->owner)
        ->deleteJson(route('boards.board-lists.destroy', [$this->board, $this->boardList]));

    $response->assertNoContent();
    expect(BoardList::withTrashed()->find($this->boardList->id))->toBeNull()
        ->and(Card::withTrashed()->whereKey([$card->id, $deletedCard->id])->exists())->toBeFalse();
});

test('lists that are not archived cannot be deleted', function () {
    $this->boardList->update(['archived_at' => null]);

    $response = $this->actingAs($this->owner)
        ->deleteJson(route('boards.board-lists.destroy', [$this->board, $this->boardList]));

    $response->assertJsonValidationErrors(['board_list' => 'Only archived lists can be deleted.']);
    $this->assertModelExists($this->boardList);
});

test('board members cannot delete lists', function () {
    $response = $this->actingAs($this->member)
        ->deleteJson(route('boards.board-lists.destroy', [$this->board, $this->boardList]));

    $response->assertForbidden()
        ->assertJsonPath('message', 'Only the workspace owner can delete lists.');
    $this->assertModelExists($this->boardList);
});

test('lists of another board are not found through this board', function () {
    $otherBoard = Board::factory()->for($this->workspace)->create();

    $response = $this->actingAs($this->owner)
        ->deleteJson(route('boards.board-lists.destroy', [$otherBoard, $this->boardList]));

    $response->assertNotFound();
    $this->assertModelExists($this->boardList);
});
