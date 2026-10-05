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
    $this->card = Card::factory()->for($this->boardList)->create();
});

test('workspace owners and board members can view, update and delete its cards', function (string $ability) {
    expect($this->owner->can($ability, $this->card))->toBeTrue()
        ->and($this->member->can($ability, $this->card))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('workspace members who are not on the board cannot view, update or delete its cards', function (string $ability) {
    $this->board->members()->detach($this->member);

    expect($this->member->can($ability, $this->card))->toBeFalse();
})->with(['view', 'update', 'delete']);

test('users outside the workspace cannot view, update or delete its cards', function (string $ability) {
    expect(User::factory()->create()->can($ability, $this->card))->toBeFalse();
})->with(['view', 'update', 'delete']);

test('members who left the workspace cannot view, update or delete its cards', function (string $ability) {
    $this->workspace->users()->detach($this->member);

    expect($this->member->can($ability, $this->card))->toBeFalse();
})->with(['view', 'update', 'delete']);

test('only workspace owners and board members can add cards to its lists', function () {
    $workspaceMemberNotOnBoard = User::factory()->create();
    $this->workspace->users()->attach($workspaceMemberNotOnBoard);

    expect($this->owner->can('create', [Card::class, $this->boardList]))->toBeTrue()
        ->and($this->member->can('create', [Card::class, $this->boardList]))->toBeTrue()
        ->and($workspaceMemberNotOnBoard->can('create', [Card::class, $this->boardList]))->toBeFalse()
        ->and(User::factory()->create()->can('create', [Card::class, $this->boardList]))->toBeFalse();
});
