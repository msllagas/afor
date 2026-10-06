<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();
    $this->board = Board::factory()->for($this->workspace)->create();
    $this->boardList = BoardList::factory()->for($this->board)->create();
    $this->card = Card::factory()->for($this->boardList)->create();
    $this->card->delete();
});

test('workspace owners can restore a deleted card', function () {
    $this->actingAs($this->user)
        ->patch(route('board-lists.cards.restore', [$this->boardList, $this->card]))
        ->assertRedirect();

    $this->assertNotSoftDeleted($this->card);
});

test('board members can restore a deleted card', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);
    $this->board->members()->attach($member);

    $this->actingAs($member)
        ->patch(route('board-lists.cards.restore', [$this->boardList, $this->card]))
        ->assertRedirect();

    $this->assertNotSoftDeleted($this->card);
});

test('workspace members who are not on the board cannot restore its cards', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);

    $this->actingAs($member)
        ->patch(route('board-lists.cards.restore', [$this->boardList, $this->card]))
        ->assertNotFound();

    $this->assertSoftDeleted($this->card);
});

test('users outside the workspace cannot restore its cards', function () {
    $this->actingAs(User::factory()->create())
        ->patch(route('board-lists.cards.restore', [$this->boardList, $this->card]))
        ->assertNotFound();

    $this->assertSoftDeleted($this->card);
});

test('cards can only be restored through their own list', function () {
    $otherList = BoardList::factory()->for($this->board)->create();

    $this->actingAs($this->user)
        ->patch(route('board-lists.cards.restore', [$otherList, $this->card]))
        ->assertNotFound();

    $this->assertSoftDeleted($this->card);
});
