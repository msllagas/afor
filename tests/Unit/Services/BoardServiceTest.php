<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Services\BoardService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();

    $this->service = app(BoardService::class);
});

test('archive board', function () {
    $board = Board::factory()->for($this->workspace)->create();

    $this->service->archive($board, $this->user);

    $this->assertDatabaseHas('boards', [
        'id'          => $board->id,
    ]);
    $this->assertNotNull($board->archived_at);
    $this->assertEquals($board->archived_by, $this->user->id);
});

test('unarchive board', function () {
    $board = Board::factory()->for($this->workspace)->archived()->create();

    $this->service->unarchive($board);

    $this->assertDatabaseHas('boards', [
        'id'          => $board->id,
    ]);
    $this->assertNull($board->archived_at);
    $this->assertNull($board->archived_by);
});
