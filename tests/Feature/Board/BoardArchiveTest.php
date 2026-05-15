<?php

use App\Models\Board;
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
            'archived_by' => null,
            'archived_at' => null,
        ]);

    $board->refresh();

    $this->assertDatabaseHas('boards', [
        'id'          => $board->id,
        'archived_by' => null,
        'archived_at' => null,
    ]);
});
