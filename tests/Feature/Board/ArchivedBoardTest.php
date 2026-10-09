<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * An archived board with a list, an archived list, a card, a deleted card and a member,
 * plus a workspace member who could still be added to it.
 */
beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->boardMember = User::factory()->create(['name' => 'Ada Lovelace']);
    $this->workspaceMember = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach([$this->boardMember->id, $this->workspaceMember->id]);
    $this->board = Board::factory()
        ->for($this->workspace)
        ->withMembers($this->boardMember)
        ->archived($this->boardMember)
        ->create();
    $this->boardList = BoardList::factory()->for($this->board)->create(['order' => 0]);
    $this->otherList = BoardList::factory()->for($this->board)->create(['order' => 1]);
    $this->archivedList = BoardList::factory()->for($this->board)->archived()->create();
    $this->card = Card::factory()->for($this->boardList)->create(['order' => 0]);
    $this->deletedCard = Card::factory()->for($this->boardList)->create(['order' => 1]);
    $this->deletedCard->delete();
});

/**
 * Everything a board, its lists, cards and members are made of, to show a refused change left it all alone.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
function archivedBoardSnapshot(): array
{
    return collect(['boards', 'board_lists', 'cards', 'board_user', 'board_user_favorites'])
        ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->get()->map(fn ($row) => (array) $row)->sortBy('id')->values()->all()])
        ->all();
}

test('the people on an archived board can open it read-only', function (string $actor, bool $isOwner) {
    $response = $this->actingAs($this->{$actor})->get(route('boards.show', $this->board));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('boards/Show')
        ->whereNot('board.archived_at', null)
        ->has('board.board_lists', 2)
        ->where('canManageMembers', false)
        ->missing('addableMembers')
        ->where('canArchive', false)
        ->where('canUnarchive', $isOwner)
        ->where('canDelete', $isOwner)
        ->where('canLeave', !$isOwner)
    );
})->with([
    'workspace owner' => ['owner', true],
    'board member'    => ['boardMember', false],
]);

test('the people on an archived board can open its cards and archived items', function (string $actor) {
    $this->actingAs($this->{$actor});

    $this->get(route('board-lists.cards.show', [$this->boardList, $this->card]))
        ->assertInertia(fn (Assert $page) => $page->where('selectedCard.id', $this->card->id));
    $this->getJson(route('boards.archived-items', $this->board))
        ->assertJsonPath('board_lists.0.id', $this->archivedList->id)
        ->assertJsonPath('cards.0.id', $this->deletedCard->id);
})->with(['owner', 'boardMember']);

test('archived boards refuse every change to the board, its lists, cards and members', function (string $actor, Closure $request) {
    [$method, $url, $data] = $request->call($this);
    $snapshot = archivedBoardSnapshot();

    $response = $this->actingAs($this->{$actor})->json($method, $url, $data);

    $response->assertForbidden();
    expect(archivedBoardSnapshot())->toEqual($snapshot);
})->with([
    'workspace owner' => 'owner',
    'board member'    => 'boardMember',
])->with([
    'rename the board'       => fn () => ['patch', route('boards.update', $this->board), ['name' => 'Renamed']],
    'archive the board'      => fn () => ['patch', route('boards.archive', $this->board), []],
    'star the board'         => fn () => ['post', route('workspaces.boards.favorite', [$this->workspace, $this->board]), []],
    'reorder the lists'      => fn () => ['patch', route('boards.board-lists.reorder', $this->board), ['boardLists' => [
        ['id' => $this->otherList->id, 'order' => 0],
        ['id' => $this->boardList->id, 'order' => 1],
    ]]],
    'add a list'             => fn () => ['post', route('boards.board-lists.store', $this->board), ['name' => 'Ideas']],
    'rename a list'          => fn () => ['patch', route('boards.board-lists.update', [$this->board, $this->boardList]), ['name' => 'Renamed']],
    'recolor a list'         => fn () => ['patch', route('boards.board-lists.update', [$this->board, $this->boardList]), ['color' => 'angel']],
    'archive a list'         => fn () => ['patch', route('boards.board-lists.archive', [$this->board, $this->boardList]), []],
    'restore a list'         => fn () => ['patch', route('boards.board-lists.unarchive', [$this->board, $this->archivedList]), []],
    'add a card'             => fn () => ['post', route('board-lists.cards.store', $this->boardList), ['name' => 'Pack the tent']],
    'rename a card'          => fn () => ['patch', route('board-lists.cards.update', [$this->boardList, $this->card]), ['name' => 'Renamed']],
    'describe a card'        => fn () => ['patch', route('board-lists.cards.update', [$this->boardList, $this->card]), ['description' => '<p>Notes</p>']],
    'move a card'            => fn () => ['patch', route('board-lists.cards.update', [$this->boardList, $this->card]), ['board_list_id' => $this->otherList->id, 'order' => 0]],
    'reorder the cards'      => fn () => ['patch', route('board-lists.cards.reorder', $this->boardList), ['cards' => [['id' => $this->card->id, 'order' => 3]]]],
    'delete a card'          => fn () => ['delete', route('board-lists.cards.destroy', [$this->boardList, $this->card]), []],
    'restore a deleted card' => fn () => ['patch', route('board-lists.cards.restore', [$this->boardList, $this->deletedCard]), []],
    'add a member'           => fn () => ['post', route('boards.members.store', $this->board), ['user_id' => $this->workspaceMember->id]],
    'remove a member'        => fn () => ['delete', route('boards.members.destroy', [$this->board, $this->boardMember]), []],
]);

test('changes to an archived board tell the user to restore it first', function () {
    $response = $this->actingAs($this->owner)->patchJson(route('boards.update', $this->board), ['name' => 'Renamed']);

    $response->assertForbidden()
        ->assertJsonPath('message', 'This board is archived. Restore it to make changes.');
});

test('board members can still leave an archived board', function () {
    $response = $this->actingAs($this->boardMember)->delete(route('boards.leave', $this->board));

    $response->assertRedirect(route('workspaces.home', $this->workspace));
    $this->assertDatabaseMissing('board_user', ['board_id' => $this->board->id, 'user_id' => $this->boardMember->id]);
});
