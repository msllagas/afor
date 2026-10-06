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

test('board list reorders must be a list of list ids and positions', function (array $payload, string $invalidField) {
    $user = User::factory()->create();
    $board = Board::factory()->for(Workspace::factory()->forUser($user))->create();
    $boardList = BoardList::factory()->for($board)->create(['order' => 0]);

    $this->actingAs($user)
        ->patch(route('boards.board-lists.reorder', $board), $payload)
        ->assertInvalid([$invalidField]);

    expect($boardList->refresh()->order)->toBe(0);
})->with([
    'no lists'                => [[], 'boardLists'],
    'lists that are text'     => [['boardLists' => 'first'], 'boardLists'],
    'an id that is no id'     => [['boardLists' => [['id' => 'not-an-id', 'order' => 1]]], 'boardLists.0.id'],
    'a negative position'     => [['boardLists' => [['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => -1]]], 'boardLists.0.order'],
    'a position that is text' => [['boardLists' => [['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => 'first']]], 'boardLists.0.order'],
    'the same list twice'     => [['boardLists' => [
        ['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => 0],
        ['id' => '0199a1b2-7c3d-7e4f-8a5b-6c7d8e9f0a1b', 'order' => 1],
    ]], 'boardLists.0.id'],
]);
