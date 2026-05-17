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

test('create board with default lists', function () {
    $data = [
        'name' => 'Test Board',
    ];

    $this->service->create($data, $this->workspace);

    $this->assertDatabaseHas('boards', $data);
    $this->assertDatabaseCount('board_lists', 3);

    $board = Board::where('name', 'Test Board')->first();

    $this->assertDatabaseHas('board_lists', ['board_id' => $board->id, 'name' => 'To Do']);
    $this->assertDatabaseHas('board_lists', ['board_id' => $board->id, 'name' => 'In Progress']);
    $this->assertDatabaseHas('board_lists', ['board_id' => $board->id, 'name' => 'Done']);
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
