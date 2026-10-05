<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
    $this->boardList = BoardList::factory()->for($this->board)->create();
});

test('workspace owners and board members can add lists to the board and update them', function () {
    expect($this->owner->can('create', [BoardList::class, $this->board]))->toBeTrue()
        ->and($this->owner->can('update', $this->boardList))->toBeTrue()
        ->and($this->member->can('create', [BoardList::class, $this->board]))->toBeTrue()
        ->and($this->member->can('update', $this->boardList))->toBeTrue();
});

test('workspace members who are not on the board cannot add lists to it or update them', function () {
    $this->board->members()->detach($this->member);

    expect($this->member->can('create', [BoardList::class, $this->board]))->toBeFalse()
        ->and($this->member->can('update', $this->boardList))->toBeFalse();
});

test('users outside the workspace cannot add lists to its boards or update them', function () {
    $outsider = User::factory()->create();

    expect($outsider->can('create', [BoardList::class, $this->board]))->toBeFalse()
        ->and($outsider->can('update', $this->boardList))->toBeFalse();
});

test('members who left the workspace cannot add lists to its boards or update them', function () {
    $this->workspace->users()->detach($this->member);

    expect($this->member->can('create', [BoardList::class, $this->board]))->toBeFalse()
        ->and($this->member->can('update', $this->boardList))->toBeFalse();
});
