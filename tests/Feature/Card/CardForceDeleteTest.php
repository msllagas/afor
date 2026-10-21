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
    $this->boardList = BoardList::factory()->for($this->board)->create();
    $this->card = Card::factory()->for($this->boardList)->create(['deleted_at' => now()]);
});

test('the workspace owner can permanently delete an archived card', function () {
    $this->actingAs($this->owner)
        ->deleteJson(route('board-lists.cards.force-delete', [$this->boardList, $this->card]))
        ->assertNoContent();

    expect(Card::withTrashed()->find($this->card->id))->toBeNull();
});

test('cards that are not archived cannot be deleted permanently', function () {
    $this->card->restore();

    $this->actingAs($this->owner)
        ->deleteJson(route('board-lists.cards.force-delete', [$this->boardList, $this->card]))
        ->assertJsonValidationErrors(['card' => 'Only archived cards can be deleted.']);

    $this->assertNotSoftDeleted($this->card);
});

test('board members cannot permanently delete cards', function () {
    $this->actingAs($this->member)
        ->deleteJson(route('board-lists.cards.force-delete', [$this->boardList, $this->card]))
        ->assertForbidden()
        ->assertJsonPath('message', 'Only the workspace owner can delete cards.');

    $this->assertSoftDeleted($this->card);
});

test('cards can only be deleted through their own list', function () {
    $otherList = BoardList::factory()->for($this->board)->create();

    $this->actingAs($this->owner)
        ->deleteJson(route('board-lists.cards.force-delete', [$otherList, $this->card]))
        ->assertNotFound();

    $this->assertSoftDeleted($this->card);
});
