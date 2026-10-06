<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('board screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertStatus(200);
});

test('users can view boards index with their own and shared workspaces', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $ownedWorkspace = Workspace::factory()->forUser($user)->create(['name' => 'Owned Workspace']);

    $sharedWorkspace = Workspace::factory()->forUser($anotherUser)->create(['name' => 'Shared Workspace']);
    $sharedWorkspace->users()->attach($user);

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Index')
            ->has('ownedWorkspaces', 1, fn (Assert $page) => $page
                ->where('id', $ownedWorkspace->id)
                ->where('name', 'Owned Workspace')
                ->has('boards')
                ->etc()
            )
            ->has('sharedWorkspaces', 1, fn (Assert $page) => $page
                ->where('id', $sharedWorkspace->id)
                ->where('name', 'Shared Workspace')
                ->has('boards')
                ->etc()
            )
        );
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('boards.index'));

    $response->assertRedirect(route('login'));
});

test('boards index excludes workspaces the user does not belong to', function () {
    $user = User::factory()->create();
    Workspace::factory()->forUser(User::factory()->create())->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces', 0)
        ->has('sharedWorkspaces', 0)
    );
});

test('boards index lists every shared workspace as a list', function () {
    $user = User::factory()->create();
    Workspace::factory()->forUser($user)->create();

    $sharedWorkspaces = Workspace::factory()
        ->forUser(User::factory()->create())
        ->count(2)
        ->create();
    $user->sharedWorkspaces()->attach($sharedWorkspaces);

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces', 1)
        ->has('sharedWorkspaces', 2)
        ->where('sharedWorkspaces', fn ($workspaces) => array_is_list($workspaces->all()))
    );
});

test('boards index hides archived boards', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $activeBoard = Board::factory()->for($workspace)->create();
    Board::factory()->for($workspace)->archived($user)->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces.0.boards', 1)
        ->where('ownedWorkspaces.0.boards.0.id', $activeBoard->id)
    );
});

test('boards index marks the boards the user starred', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $starredBoard = Board::factory()->for($workspace)->create(['created_at' => now()->subDay()]);
    $otherBoard = Board::factory()->for($workspace)->create();
    $user->favoriteBoards()->attach($starredBoard);
    User::factory()->create()->favoriteBoards()->attach($otherBoard);

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('ownedWorkspaces.0.boards.0.id', $starredBoard->id)
        ->where('ownedWorkspaces.0.boards.0.is_favorited', true)
        ->where('ownedWorkspaces.0.boards.1.is_favorited', false)
    );
});

test('boards index previews each board with its active lists and card counts', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->create();
    $activeList = BoardList::factory()->for($board)->create(['color' => 'angel']);
    BoardList::factory()->for($board)->archived()->create();
    Card::factory()->count(3)->for($activeList)->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces.0.boards.0.board_lists', 1, fn (Assert $list) => $list
            ->where('id', $activeList->id)
            ->where('color', 'angel')
            ->where('cards_count', 3)
            ->etc()
        )
    );
});

test('boards index lists every board of owned workspaces but only the boards a member was added to', function () {
    $user = User::factory()->create();
    $ownedWorkspace = Workspace::factory()->forUser($user)->create();
    Board::factory()->for($ownedWorkspace)->count(2)->create();
    $sharedWorkspace = Workspace::factory()->create();
    $sharedWorkspace->users()->attach($user);
    $boardUserIsOn = Board::factory()->for($sharedWorkspace)->withMembers($user)->create();
    Board::factory()->for($sharedWorkspace)->create();

    $response = $this->actingAs($user)->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('ownedWorkspaces.0.boards', 2)
        ->has('sharedWorkspaces.0.boards', 1)
        ->where('sharedWorkspaces.0.boards.0.id', $boardUserIsOn->id)
    );
});

test('boards index lists a shared workspace with no boards when the member was added to none', function () {
    $user = User::factory()->create();
    $sharedWorkspace = Workspace::factory()->create();
    $sharedWorkspace->users()->attach($user);
    Board::factory()->for($sharedWorkspace)->create();

    $response = $this->actingAs($user)->get(route('boards.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('sharedWorkspaces', 1)
        ->has('sharedWorkspaces.0.boards', 0)
    );
});
