<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
});

test('archived lists leave the board until they are restored with their cards', function () {
    $boardList = BoardList::factory()->for($this->board)->create(['name' => 'Doing']);
    $card = Card::factory()->for($boardList)->create();

    $this->actingAs($this->member)
        ->patch(route('boards.board-lists.archive', [$this->board, $boardList]))
        ->assertRedirect();

    $this->get(route('boards.show', $this->board))
        ->assertInertia(fn (Assert $page) => $page->has('board.board_lists', 0));

    $this->patch(route('boards.board-lists.unarchive', [$this->board, $boardList]))
        ->assertRedirect();

    expect($boardList->fresh())->archived_at->toBeNull()->archived_by->toBeNull();
    $this->get(route('boards.show', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->where('board.board_lists.0.id', $boardList->id)
            ->where('board.board_lists.0.cards.0.id', $card->id)
        );
});

test('archived items lists archived lists and deleted cards of the board, most recent first', function () {
    $archiver = User::factory()->create(['name' => 'Ada Lovelace']);
    $olderList = BoardList::factory()->for($this->board)->archived($archiver)->create(['archived_at' => now()->subDay()]);
    $newerList = BoardList::factory()->for($this->board)->archived($archiver)->create(['color' => 'angel']);
    Card::factory()->count(2)->for($newerList)->create();
    $activeList = BoardList::factory()->for($this->board)->create(['name' => 'Doing']);
    Card::factory()->for($activeList)->create();
    $olderCard = Card::factory()->for($activeList)->create(['deleted_at' => now()->subHour()]);
    $newerCard = Card::factory()->for($activeList)->create(['deleted_at' => now()]);

    $this->actingAs($this->member)
        ->getJson(route('boards.archived-items', $this->board))
        ->assertOk()
        ->assertJsonCount(2, 'board_lists')
        ->assertJsonPath('board_lists.0.id', $newerList->id)
        ->assertJsonPath('board_lists.0.color', 'angel')
        ->assertJsonPath('board_lists.0.archiver.name', 'Ada Lovelace')
        ->assertJsonPath('board_lists.0.cards_count', 2)
        ->assertJsonPath('board_lists.1.id', $olderList->id)
        ->assertJsonCount(2, 'cards')
        ->assertJsonPath('cards.0.id', $newerCard->id)
        ->assertJsonPath('cards.0.board_list.name', 'Doing')
        ->assertJsonPath('cards.1.id', $olderCard->id);
});

test('cards deleted from an archived list wait for the list to be restored', function () {
    $archivedList = BoardList::factory()->for($this->board)->archived()->create();
    Card::factory()->for($archivedList)->create(['deleted_at' => now()]);

    $this->actingAs($this->member)
        ->getJson(route('boards.archived-items', $this->board))
        ->assertJsonCount(1, 'board_lists')
        ->assertJsonCount(0, 'cards');
});

test('archived items leave out other boards', function () {
    $otherBoard = Board::factory()->for($this->workspace)->create();
    BoardList::factory()->for($otherBoard)->archived()->create();
    Card::factory()->for(BoardList::factory()->for($otherBoard))->create(['deleted_at' => now()]);

    $this->actingAs($this->owner)
        ->getJson(route('boards.archived-items', $this->board))
        ->assertExactJson(['board_lists' => [], 'cards' => []]);
});

test('lists stay archived after the member who archived them deletes their account', function () {
    $archiver = User::factory()->create();
    $boardList = BoardList::factory()->for($this->board)->archived($archiver)->create();

    $archiver->delete();

    $this->actingAs($this->owner)
        ->getJson(route('boards.archived-items', $this->board))
        ->assertJsonPath('board_lists.0.id', $boardList->id)
        ->assertJsonPath('board_lists.0.archiver', null);
});
