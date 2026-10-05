<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * A member leaves the workspace in one tab while its board is still open in another.
 * Nothing the board page can send should work for them afterwards.
 */
beforeEach(function () {
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser()->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create(['name' => 'Roadmap']);
    $this->archivedBoard = Board::factory()->for($this->workspace)->withMembers($this->member)->archived()->create();
    $this->boardList = BoardList::factory()->for($this->board)->create(['name' => 'Doing']);
    $this->otherList = BoardList::factory()->for($this->board)->create();
    $this->card = Card::factory()->for($this->boardList)->create(['name' => 'Ship it']);

    $this->actingAs($this->member)
        ->delete(route('workspaces.leave', ['workspace' => $this->workspace]))
        ->assertRedirect(route('boards.index'));
});

/**
 * Everything the board page, card dialog and archived boards dialog can request.
 *
 * @return array<string, Closure(): array{0: string, 1: string, 2?: array<string, mixed>}>
 */
dataset('workspace requests', [
    'open the board'            => fn () => ['get', route('boards.show', $this->board)],
    'rename the board'          => fn () => ['patch', route('boards.update', $this->board), ['name' => 'Hijacked']],
    'archive the board'         => fn () => ['patch', route('boards.archive', $this->board)],
    'restore an archived board' => fn () => ['patch', route('boards.unarchive', $this->archivedBoard)],
    'delete an archived board'  => fn () => ['delete', route('boards.destroy', $this->archivedBoard)],
    'star the board'            => fn () => ['post', route('workspaces.boards.favorite', [$this->workspace, $this->board])],
    'see archived boards'       => fn () => ['get', route('workspaces.boards.archived', $this->workspace)],
    'add a board'               => fn () => ['post', route('workspaces.boards.store', $this->workspace), ['name' => 'New board']],
    'reorder lists'             => fn () => ['patch', route('boards.board-lists.reorder', $this->board), [
        'boardLists' => [['id' => $this->boardList->id, 'order' => 5]],
    ]],
    'add a list'    => fn () => ['post', route('boards.board-lists.store', $this->board), ['name' => 'New list']],
    'rename a list' => fn () => ['patch', route('boards.board-lists.update', [$this->board, $this->boardList]), [
        'name' => 'Hijacked',
    ]],
    'archive a list' => fn () => ['patch', route('boards.board-lists.update', [$this->board, $this->boardList]), [
        'is_archived' => true,
    ]],
    'reorder cards' => fn () => ['patch', route('board-lists.cards.reorder', $this->boardList), [
        'cards' => [['id' => $this->card->id, 'order' => 5]],
    ]],
    'add a card'    => fn () => ['post', route('board-lists.cards.store', $this->boardList), ['name' => 'New card']],
    'open a card'   => fn () => ['get', route('board-lists.cards.show', [$this->boardList, $this->card])],
    'rename a card' => fn () => ['patch', route('board-lists.cards.update', [$this->boardList, $this->card]), [
        'name' => 'Hijacked',
    ]],
    'move a card' => fn () => ['patch', route('board-lists.cards.update', [$this->boardList, $this->card]), [
        'board_list_id' => $this->otherList->id,
        'order'         => 0,
    ]],
    'delete a card' => fn () => ['delete', route('board-lists.cards.destroy', [$this->boardList, $this->card])],
]);

test('members who left a workspace get not found for its boards, lists and cards', function (Closure $request) {
    [$method, $url, $payload] = [...$request->call($this), []];
    $tables = ['boards', 'board_lists', 'cards', 'board_user', 'board_user_favorites'];
    $before = collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->get()]);

    $response = $this->json($method, $url, $payload);

    $response->assertNotFound();
    foreach ($tables as $table) {
        expect(DB::table($table)->get())->toEqual($before[$table]);
    }
})->with('workspace requests');

test('members who stay in the workspace can still open the boards and cards they were added to', function () {
    $stayingMember = User::factory()->create();
    $this->workspace->users()->attach($stayingMember);
    $this->board->members()->attach($stayingMember);

    $this->actingAs($stayingMember)->get(route('boards.show', $this->board))->assertOk();
    $this->actingAs($stayingMember)
        ->get(route('board-lists.cards.show', [$this->boardList, $this->card]))
        ->assertOk();
});
