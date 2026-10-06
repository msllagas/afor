<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();
});

test('users can archive boards', function () {

    $board = Board::factory()->for($this->workspace)->unarchived()->create();

    $this->actingAs($this->user)
        ->patch(route('boards.archive', [
            'board' => $board,
        ]))
        ->assertRedirect();

    $board->refresh();

    $this->assertDatabaseHas('boards', [
        'id'          => $board->id,
        'archived_by' => $this->user->id,
    ]);

    $this->assertNotNull($board->archived_at);
});

test('user can unarchive boards', function () {
    $board = Board::factory()->for($this->workspace)->archived()->create();

    $this->actingAs($this->user)
        ->patch(route('boards.unarchive', [
            'board' => $board,
        ]))->assertJson([
            'id'          => $board->id,
            'archived_at' => null,
        ]);

    $board->refresh();

    $this->assertDatabaseHas('boards', [
        'id'          => $board->id,
        'archived_by' => null,
        'archived_at' => null,
    ]);
});

test('archived boards are listed most recently archived first with their archiver', function () {
    $archiver = User::factory()->create(['name' => 'Ada Lovelace']);
    $olderBoard = Board::factory()->for($this->workspace)->archived($archiver)->create(['archived_at' => now()->subDay()]);
    $newerBoard = Board::factory()->for($this->workspace)->archived($archiver)->create();
    Board::factory()->for($this->workspace)->unarchived()->create();
    Board::factory()->archived($archiver)->create();

    $this->actingAs($this->user)
        ->getJson(route('workspaces.boards.archived', $this->workspace))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.id', $newerBoard->id)
        ->assertJsonPath('0.archiver.name', 'Ada Lovelace')
        ->assertJsonPath('1.id', $olderBoard->id);
});

test('boards stay archived after the member who archived them deletes their account', function () {
    $archiver = User::factory()->create();
    $board = Board::factory()->for($this->workspace)->archived($archiver)->create();

    $archiver->delete();

    $this->actingAs($this->user)
        ->getJson(route('workspaces.boards.archived', $this->workspace))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $board->id)
        ->assertJsonPath('0.archiver', null);
});

test('the owner can delete an archived board whose archiver deleted their account', function () {
    $archiver = User::factory()->create();
    $board = Board::factory()->for($this->workspace)->archived($archiver)->create();

    $archiver->delete();

    $this->actingAs($this->user)
        ->deleteJson(route('boards.destroy', $board))
        ->assertNoContent();

    $this->assertModelMissing($board);
});

test('archived boards are previewed with their active lists and the user star', function () {
    $board = Board::factory()->for($this->workspace)->archived($this->user)->create();
    $activeList = BoardList::factory()->for($board)->create(['color' => 'angel']);
    BoardList::factory()->for($board)->create(['is_archived' => true]);
    Card::factory()->count(2)->for($activeList)->create();
    $this->user->favoriteBoards()->attach($board);

    $this->actingAs($this->user)
        ->getJson(route('workspaces.boards.archived', $this->workspace))
        ->assertJsonPath('0.is_favorited', true)
        ->assertJsonCount(1, '0.board_lists')
        ->assertJsonPath('0.board_lists.0.id', $activeList->id)
        ->assertJsonPath('0.board_lists.0.color', 'angel')
        ->assertJsonPath('0.board_lists.0.cards_count', 2);
});

test('users outside the workspace cannot list its archived boards', function () {
    Board::factory()->for($this->workspace)->archived($this->user)->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('workspaces.boards.archived', $this->workspace))
        ->assertNotFound();
});

test('users outside the workspace cannot archive its boards', function () {
    $board = Board::factory()->for($this->workspace)->unarchived()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('boards.archive', $board))
        ->assertNotFound();

    expect($board->refresh()->archived_at)->toBeNull();
});

test('users outside the workspace cannot unarchive its boards', function () {
    $board = Board::factory()->for($this->workspace)->archived($this->user)->create();

    $this->actingAs(User::factory()->create())
        ->patchJson(route('boards.unarchive', $board))
        ->assertNotFound();

    expect($board->refresh()->archived_at)->not->toBeNull();
});

test('boards must be archived before they can be deleted', function () {
    $board = Board::factory()->for($this->workspace)->unarchived()->create();

    $this->actingAs($this->user)
        ->deleteJson(route('boards.destroy', $board))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['board' => 'Only archived boards can be deleted.']);

    $this->assertModelExists($board);
});

test('users outside the workspace cannot delete its archived boards', function () {
    $board = Board::factory()->for($this->workspace)->archived($this->user)->create();

    $this->actingAs(User::factory()->create())
        ->deleteJson(route('boards.destroy', $board))
        ->assertNotFound();

    $this->assertModelExists($board);
});

test('archived boards leave out boards the member is not on', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);
    $boardMemberIsOn = Board::factory()->for($this->workspace)->withMembers($member)->archived($this->user)->create();
    Board::factory()->for($this->workspace)->archived($this->user)->create();

    $this->actingAs($member)
        ->getJson(route('workspaces.boards.archived', $this->workspace))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $boardMemberIsOn->id);
});
