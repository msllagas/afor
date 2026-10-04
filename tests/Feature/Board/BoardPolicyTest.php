<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->board = Board::factory()->for($this->workspace)->create();
});

test('workspace owners and members can update and delete its boards', function (string $ability) {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);

    expect($this->owner->can($ability, $this->board))->toBeTrue()
        ->and($member->can($ability, $this->board))->toBeTrue();
})->with(['update', 'delete']);

test('users outside the workspace cannot update or delete its boards', function (string $ability) {
    $response = User::factory()->create()->can($ability, $this->board);

    expect($response)->toBeFalse();
})->with(['update', 'delete']);
