<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

/**
 * Alex owns one workspace, is a member of a second and used to be a member of a third,
 * where their board row was left behind. A fourth workspace has nothing to do with them.
 */
beforeEach(function () {
    $this->alex = User::factory()->create();
    $this->teammate = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->ownedWorkspace = Workspace::factory()->forUser($this->alex)->create();
    $this->sharedWorkspace = Workspace::factory()->create();
    $this->formerWorkspace = Workspace::factory()->create();
    $this->sharedWorkspace->users()->attach([$this->alex->id, $this->teammate->id]);

    $this->ownedBoards = Board::factory()->for($this->ownedWorkspace)->count(2)->create();
    $this->boardAlexIsOn = Board::factory()->for($this->sharedWorkspace)->withMembers($this->alex)->create();
    $this->boardAlexIsNotOn = Board::factory()->for($this->sharedWorkspace)->withMembers($this->teammate)->create();
    $this->formerBoard = Board::factory()->for($this->formerWorkspace)->withMembers($this->alex)->create();
    $this->unrelatedBoard = Board::factory()->create();
});

test('users see every board in workspaces they own and only the boards they were added to elsewhere', function () {
    $visibleBoardIds = Board::query()->visibleTo($this->alex)->pluck('id')->sort()->values()->all();

    expect($visibleBoardIds)->toBe(
        collect([...$this->ownedBoards->modelKeys(), $this->boardAlexIsOn->id])->sort()->values()->all()
    );
});

test('the visibleTo scope and isAccessibleBy agree for every user', function (string $actor) {
    $user = $this->{$actor};
    $accessibleBoardIds = Board::with('workspace')->get()
        ->filter(fn (Board $board) => $board->isAccessibleBy($user))
        ->values()
        ->modelKeys();

    $visibleBoardIds = Board::query()->visibleTo($user)->pluck('id')->all();

    expect($visibleBoardIds)->toEqualCanonicalizing($accessibleBoardIds);
})->with([
    'owner of one workspace and member of others' => 'alex',
    'member of a shared workspace'                => 'teammate',
    'user outside every workspace'                => 'outsider',
]);
