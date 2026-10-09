<?php

use App\Events\BoardChanged;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspaceMember = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach([$this->member->id, $this->workspaceMember->id]);

    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
    $this->backlog = BoardList::factory()->for($this->board)->create(['order' => 0]);
    $this->doing = BoardList::factory()->for($this->board)->create(['order' => 1]);
    $this->card = Card::factory()->for($this->backlog)->create(['order' => 0]);

    $this->otherBoard = Board::factory()->for(Workspace::factory()->forUser())->create();

    Event::fake([BoardChanged::class]);
});

function changeBoard(string $change): TestResponse
{
    $test = test();

    return match ($change) {
        'adding a card' => $test->actingAs($test->member)
            ->post(route('board-lists.cards.store', $test->backlog), ['name' => 'Pack the tent']),
        'renaming a card' => $test->actingAs($test->member)
            ->patch(route('board-lists.cards.update', [$test->backlog, $test->card]), ['name' => 'Pack the stove']),
        'describing a card' => tap($test->actingAs($test->member), fn () => $test->card->update(['description' => null]))
            ->patch(route('board-lists.cards.update', [$test->backlog, $test->card]), ['description' => '<p>Two poles</p>']),
        'clearing a description' => $test->actingAs($test->member)
            ->patch(route('board-lists.cards.update', [$test->backlog, $test->card]), ['description' => '<p></p>']),
        'moving a card to another list' => $test->actingAs($test->member)
            ->patch(route('board-lists.cards.update', [$test->backlog, $test->card]), ['board_list_id' => $test->doing->id, 'order' => 0]),
        'reordering cards' => $test->actingAs($test->member)
            ->patch(route('board-lists.cards.reorder', $test->backlog), ['cards' => [['id' => $test->card->id, 'order' => 3]]]),
        'deleting a card' => $test->actingAs($test->member)
            ->delete(route('board-lists.cards.destroy', [$test->backlog, $test->card])),
        'restoring a card' => tap($test->actingAs($test->member), fn () => $test->card->delete())
            ->patch(route('board-lists.cards.restore', [$test->backlog, $test->card])),
        'adding a list' => $test->actingAs($test->member)
            ->post(route('boards.board-lists.store', $test->board), ['name' => 'Done']),
        'renaming a list' => $test->actingAs($test->member)
            ->patch(route('boards.board-lists.update', [$test->board, $test->backlog]), ['name' => 'Ideas']),
        'recolouring a list' => $test->actingAs($test->member)
            ->patch(route('boards.board-lists.update', [$test->board, $test->backlog]), ['color' => 'blue']),
        'reordering lists' => $test->actingAs($test->member)
            ->patch(route('boards.board-lists.reorder', $test->board), ['boardLists' => [['id' => $test->backlog->id, 'order' => 5]]]),
        'archiving a list' => $test->actingAs($test->member)
            ->patch(route('boards.board-lists.archive', [$test->board, $test->backlog])),
        'restoring a list' => tap($test->actingAs($test->member), fn () => $test->backlog->update(['archived_at' => now()]))
            ->patch(route('boards.board-lists.unarchive', [$test->board, $test->backlog])),
        'renaming the board' => $test->actingAs($test->member)
            ->patch(route('boards.update', $test->board), ['name' => 'Camping trip']),
        'archiving the board' => $test->actingAs($test->owner)
            ->patch(route('boards.archive', $test->board)),
        'restoring the board' => tap($test->actingAs($test->owner), fn () => $test->board->update(['archived_at' => now()]))
            ->patch(route('boards.unarchive', $test->board)),
        'deleting the board' => tap($test->actingAs($test->owner), fn () => $test->board->update(['archived_at' => now()]))
            ->delete(route('boards.destroy', $test->board)),
        'adding a board member' => $test->actingAs($test->owner)
            ->post(route('boards.members.store', $test->board), ['user_id' => $test->workspaceMember->id]),
        'removing a board member' => $test->actingAs($test->owner)
            ->delete(route('boards.members.destroy', [$test->board, $test->member])),
        'leaving the board' => $test->actingAs($test->member)
            ->delete(route('boards.leave', $test->board)),
    };
}

function changeBoardWithoutPermission(string $change): TestResponse
{
    $test = test();

    return match ($change) {
        'adding a card to an archived board' => tap($test->actingAs($test->member), fn () => $test->board->update(['archived_at' => now()]))
            ->postJson(route('board-lists.cards.store', $test->backlog), ['name' => 'Pack the tent']),
        'renaming an archived board' => tap($test->actingAs($test->member), fn () => $test->board->update(['archived_at' => now()]))
            ->patchJson(route('boards.update', $test->board), ['name' => 'Camping trip']),
        'adding a board member to an archived board' => tap($test->actingAs($test->owner), fn () => $test->board->update(['archived_at' => now()]))
            ->postJson(route('boards.members.store', $test->board), ['user_id' => $test->workspaceMember->id]),
        'adding a card from outside the board' => $test->actingAs($test->workspaceMember)
            ->postJson(route('board-lists.cards.store', $test->backlog), ['name' => 'Pack the tent']),
        'adding a card without a name' => $test->actingAs($test->member)
            ->postJson(route('board-lists.cards.store', $test->backlog), ['name' => '']),
        'deleting a board that is not archived' => $test->actingAs($test->owner)
            ->deleteJson(route('boards.destroy', $test->board)),
        'removing a board member as a member' => $test->actingAs($test->member)
            ->deleteJson(route('boards.members.destroy', [$test->board, $test->member])),
    };
}

/**
 * The boards that were told something changed, so a test can name exactly which.
 *
 * @return array<int, string>
 */
function boardsTold(): array
{
    return Event::dispatched(BoardChanged::class)
        ->map(fn (array $arguments) => $arguments[0]->boardId)
        ->unique()
        ->values()
        ->all();
}

test('every change to a board tells the people on it and its workspace', function (string $change) {
    changeBoard($change)->assertSessionHasNoErrors();

    Event::assertDispatchedTimes(BoardChanged::class, 1);
    expect(boardsTold())->toBe([$this->board->id]);
    Event::assertDispatched(BoardChanged::class, fn (BoardChanged $event) => $event->workspaceId === $this->workspace->id);
})->with([
    'adding a card',
    'renaming a card',
    'describing a card',
    'clearing a description',
    'moving a card to another list',
    'reordering cards',
    'deleting a card',
    'restoring a card',
    'adding a list',
    'renaming a list',
    'recolouring a list',
    'reordering lists',
    'archiving a list',
    'restoring a list',
    'renaming the board',
    'archiving the board',
    'restoring the board',
    'deleting the board',
    'adding a board member',
    'removing a board member',
    'leaving the board',
]);

test('creating a board tells its workspace', function () {
    $this->actingAs($this->owner)->post(route('workspaces.boards.store', $this->workspace), ['name' => 'Camping trip']);

    $board = $this->workspace->boards()->where('name', 'Camping trip')->sole();
    Event::assertDispatched(BoardChanged::class, fn (BoardChanged $event) => $event->boardId === $board->id
        && $event->workspaceId === $this->workspace->id);
});

test('a change the board refuses tells nobody', function (string $change, int $status) {
    changeBoardWithoutPermission($change)->assertStatus($status);

    Event::assertNotDispatched(BoardChanged::class);
})->with([
    'a card on an archived board'         => ['adding a card to an archived board', 403],
    'a rename of an archived board'       => ['renaming an archived board', 403],
    'a member added to an archived board' => ['adding a board member to an archived board', 403],
    'a card added from outside'           => ['adding a card from outside the board', 404],
    'a card without a name'               => ['adding a card without a name', 422],
    'a board that is not archived'        => ['deleting a board that is not archived', 422],
    'a member removed by a member'        => ['removing a board member as a member', 403],
]);

test('rewording a description tells nobody, since the board only shows that there is one', function () {
    $this->actingAs($this->member)
        ->patch(route('board-lists.cards.update', [$this->backlog, $this->card]), ['description' => '<p>Two poles</p>'])
        ->assertSessionHasNoErrors();

    expect($this->card->refresh()->description)->toBe('<p>Two poles</p>');
    Event::assertNotDispatched(BoardChanged::class);
});

test('removing someone from a workspace tells each board they were on', function (string $actor) {
    $secondBoard = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
    $boardTheyWereNotOn = Board::factory()->for($this->workspace)->withMembers($this->workspaceMember)->create();

    $actor === 'the owner removing them'
        ? $this->actingAs($this->owner)->delete(route('workspaces.members.user.destroy', [$this->workspace, $this->member]))
        : $this->actingAs($this->member)->delete(route('workspaces.leave', $this->workspace));

    expect(boardsTold())->toEqualCanonicalizing([$this->board->id, $secondBoard->id])
        ->not->toContain($boardTheyWereNotOn->id, $this->otherBoard->id);
})->with([
    'the owner removing them',
    'them leaving',
]);

test('deleting a workspace tells each of its boards', function () {
    $secondBoard = Board::factory()->for($this->workspace)->create();

    $this->actingAs($this->owner)->delete(route('workspaces.destroy', $this->workspace));

    expect(boardsTold())->toEqualCanonicalizing([$this->board->id, $secondBoard->id])
        ->not->toContain($this->otherBoard->id);
});

test('deleting an account tells the boards it takes people off of', function (string $actor) {
    $secondBoard = Board::factory()->for($this->workspace)->create();

    $this->actingAs($this->{$actor})->delete(route('profile.destroy'), ['password' => 'password']);

    expect(boardsTold())->toEqualCanonicalizing(
        $actor === 'owner' ? [$this->board->id, $secondBoard->id] : [$this->board->id]
    );
})->with([
    'owner',
    'member',
]);

test('the tab that made the change is the one left out', function () {
    $this->withHeader('X-Socket-ID', '123.456')
        ->actingAs($this->member)
        ->post(route('board-lists.cards.store', $this->backlog), ['name' => 'Pack the tent']);

    Event::assertDispatched(BoardChanged::class, fn (BoardChanged $event) => $event->socket === '123.456');
});

test('the event goes to the board and its workspace and carries nothing', function () {
    $event = new BoardChanged($this->board);

    expect($event->broadcastOn())->toEqual([
        new PrivateChannel("board.{$this->board->id}"),
        new PrivateChannel("workspace.{$this->workspace->id}"),
    ])
        ->and($event->broadcastAs())->toBe('board.changed')
        ->and($event->broadcastWith())->toBe([]);
});
