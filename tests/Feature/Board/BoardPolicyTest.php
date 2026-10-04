<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->board = Board::factory()->for($this->workspace)->create();
});

test('workspace owners and members can view, update and delete its boards', function (string $ability) {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);

    expect($this->owner->can($ability, $this->board))->toBeTrue()
        ->and($member->can($ability, $this->board))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('users outside the workspace cannot view, update or delete its boards', function (string $ability) {
    $response = User::factory()->create()->can($ability, $this->board);

    expect($response)->toBeFalse();
})->with(['view', 'update', 'delete']);

test('members who left the workspace cannot view, update or delete its boards', function (string $ability) {
    $formerMember = User::factory()->create();
    $this->workspace->users()->attach($formerMember);
    $this->workspace->users()->detach($formerMember);

    expect($formerMember->can($ability, $this->board))->toBeFalse();
})->with(['view', 'update', 'delete']);

test('only workspace owners and members can add boards to it', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);

    expect($this->owner->can('create', [Board::class, $this->workspace]))->toBeTrue()
        ->and($member->can('create', [Board::class, $this->workspace]))->toBeTrue()
        ->and(User::factory()->create()->can('create', [Board::class, $this->workspace]))->toBeFalse();
});
