<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

/**
 * A board with a list, a card and a member, plus two people who can't reach it:
 * a workspace member who was never added and a user from another workspace.
 */
beforeEach(function () {
    $this->workspace = Workspace::factory()->forUser()->create();
    $this->boardMember = User::factory()->create();
    $this->workspaceMember = User::factory()->create();
    $this->workspace->users()->attach([$this->boardMember->id, $this->workspaceMember->id]);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->boardMember)->archived()->create();
    $this->boardList = BoardList::factory()->for($this->board)->create();
    $this->card = Card::factory()->for($this->boardList)->create();

    $this->outsider = User::factory()->create();
    Workspace::factory()->forUser($this->outsider)->create();
});

/**
 * Every route that takes a board, list, card or board member, so a new endpoint is covered the moment it's added.
 *
 * @return array<int, Route>
 */
function boardScopedRoutes(): array
{
    return collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route) => array_intersect($route->parameterNames(), ['board', 'board_list', 'card', 'member']))
        ->values()
        ->all();
}

test('every board, list, card and board member route is not found for people who are not on the board', function (string $actor) {
    $parameters = [
        'workspace'  => $this->workspace,
        'board'      => $this->board,
        'board_list' => $this->boardList,
        'card'       => $this->card,
        'member'     => $this->boardMember,
    ];
    $routes = boardScopedRoutes();

    $unguardedRoutes = collect($routes)
        ->map(function (Route $route) use ($actor, $parameters) {
            $method = collect($route->methods())->first(fn (string $method) => $method !== 'HEAD');
            $url = route($route->getName(), array_intersect_key($parameters, array_flip($route->parameterNames())));

            $status = $this->actingAs($this->{$actor})->json($method, $url)->status();

            return $status === 404 ? null : "{$method} {$route->uri()} returned {$status}";
        })
        ->filter()
        ->values()
        ->all();

    expect($routes)->not->toBeEmpty()
        ->and($unguardedRoutes)->toBe([]);
    $this->assertDatabaseHas('board_user', ['board_id' => $this->board->id, 'user_id' => $this->boardMember->id]);
    $this->assertModelExists($this->board);
    $this->assertModelExists($this->card);
})->with([
    'workspace member not on the board' => 'workspaceMember',
    'user from another workspace'       => 'outsider',
]);

test('lists and cards are not found through a board or list they do not belong to', function (Closure $request) {
    $otherBoard = Board::factory()->for($this->workspace)->withMembers($this->boardMember)->create();
    $otherList = BoardList::factory()->for($otherBoard)->create();
    [$method, $url] = $request->call($this, $otherBoard, $otherList);

    $response = $this->actingAs($this->boardMember)->json($method, $url, ['name' => 'Hijacked']);

    $response->assertNotFound();
    expect($this->boardList->fresh()->name)->not->toBe('Hijacked')
        ->and($this->card->fresh()->name)->not->toBe('Hijacked');
})->with([
    'rename a list through another board' => fn (Board $otherBoard) => [
        'patch', route('boards.board-lists.update', [$otherBoard, $this->boardList]),
    ],
    'open a card through another list' => fn (Board $otherBoard, BoardList $otherList) => [
        'get', route('board-lists.cards.show', [$otherList, $this->card]),
    ],
    'rename a card through another list' => fn (Board $otherBoard, BoardList $otherList) => [
        'patch', route('board-lists.cards.update', [$otherList, $this->card]),
    ],
    'delete a card through another list' => fn (Board $otherBoard, BoardList $otherList) => [
        'delete', route('board-lists.cards.destroy', [$otherList, $this->card]),
    ],
    'remove a member through a board they are not on' => fn (Board $otherBoard) => [
        'delete', route('boards.members.destroy', [$otherBoard, User::factory()->create()]),
    ],
]);
