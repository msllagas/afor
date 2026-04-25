<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

test('users can reorder board lists in board', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->create();

    $order = 0;
    $boardList1 = BoardList::factory()->for($board)->create([
        'order' => $order++,
    ]);
    $boardList2 = BoardList::factory()->for($board)->create([
        'order' => $order++,
    ]);
    $boardList3 = BoardList::factory()->for($board)->create([
        'order' => $order++,
    ]);
    $boardList4 = BoardList::factory()->for($board)->create([
        'order' => $order++,
    ]);

    // Move boardList1 next to boardList3 shifting all boardLists in between
    $payload = [
        'boardLists' => [
            ['id' => $boardList1->id, 'order' => 2],
            ['id' => $boardList2->id, 'order' => 0],
            ['id' => $boardList3->id, 'order' => 1],
        ],
    ];

    $response = patch(route('boards.board-lists.reorder', [
        'board' => $board,
    ]), $payload);

    assertDatabaseHas('board_lists', [
        'id'    => $boardList1->id,
        'order' => 2,
    ]);

    assertDatabaseHas('board_lists', [
        'id'    => $boardList2->id,
        'order' => 0,
    ]);

    assertDatabaseHas('board_lists', [
        'id'    => $boardList3->id,
        'order' => 1,
    ]);

    // Still the same
    assertDatabaseHas('board_lists', [
        'id'    => $boardList4->id,
        'order' => 3,
    ]);

    $orders = $board->boardLists()->orderBy('order')->pluck('id')->toArray();

    expect($orders)->toBe([
        $boardList2->id,
        $boardList3->id,
        $boardList1->id,
        $boardList4->id,
    ]);
});
