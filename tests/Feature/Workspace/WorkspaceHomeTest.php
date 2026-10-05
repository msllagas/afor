<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can access their workspace home', function () {
    $user = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->forUser($user)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($user)
        ->get(route('workspaces.home', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Home')
            ->has('workspace', fn (Assert $page) => $page
                ->where('id', $workspace->id)
                ->where('name', $workspace->name)
                ->where('description', $workspace->description)
                ->etc()
            )
            ->has('members', fn (Assert $page) => $page
                ->where('0.id', $member->id)
                ->where('0.name', $member->name)
                ->where('0.avatar', $member->avatar)
            )
            ->where('canInvite', true)
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('inviteLink')
                ->has('boards')
            )
        );
});

test('workspace members get their workspace home without an invite link', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member->id);

    $this->actingAs($member)
        ->get(route('workspaces.home', ['workspace' => $workspace]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canInvite', false)
            ->missing('inviteLink')
            ->loadDeferredProps(fn (Assert $reload) => $reload->has('boards'))
            ->reload(fn (Assert $reload) => $reload->missing('inviteLink'), only: 'inviteLink')
        );

    $this->assertDatabaseMissing('workspace_invitations', ['workspace_id' => $workspace->id]);
});

test('workspace members can access their workspace home', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->forUser($owner)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->get(route('workspaces.home', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Home')
            ->has('workspace', fn (Assert $page) => $page
                ->where('id', $workspace->id)
                ->where('name', $workspace->name)
                ->where('description', $workspace->description)
                ->etc()
            )
            ->has('members', fn (Assert $page) => $page
                ->where('0.id', $member->id)
                ->where('0.name', $member->name)
                ->where('0.avatar', $member->avatar)
                ->etc()
            )
        );
});

test('user that is not a member or owner of the workspace are redirected to dashboard', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $workspace = Workspace::factory()->forUser($anotherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.home', [
            'workspace' => $workspace,
        ]));

    $response->assertRedirect(route('dashboard'));
});

test('workspace home lists unarchived boards with the user stars', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $starredBoard = Board::factory()->for($workspace)->create(['created_at' => now()->subDay()]);
    $otherBoard = Board::factory()->for($workspace)->create();
    Board::factory()->for($workspace)->archived($user)->create();
    $user->favoriteBoards()->attach($starredBoard);
    User::factory()->create()->favoriteBoards()->attach($otherBoard);

    $response = $this->actingAs($user)
        ->get(route('workspaces.home', $workspace));

    $response->assertInertia(fn (Assert $page) => $page
        ->loadDeferredProps(fn (Assert $reload) => $reload
            ->has('boards', 2)
            ->where('boards.0.id', $starredBoard->id)
            ->where('boards.0.is_favorited', true)
            ->where('boards.1.id', $otherBoard->id)
            ->where('boards.1.is_favorited', false)
        )
    );
});

test('workspace home previews each board with its active lists and card counts', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $board = Board::factory()->for($workspace)->create();
    $activeList = BoardList::factory()->for($board)->create(['color' => 'angel']);
    BoardList::factory()->for($board)->create(['is_archived' => true]);
    Card::factory()->count(3)->for($activeList)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.home', $workspace));

    $response->assertInertia(fn (Assert $page) => $page
        ->loadDeferredProps(fn (Assert $reload) => $reload
            ->has('boards.0.board_lists', 1, fn (Assert $list) => $list
                ->where('id', $activeList->id)
                ->where('color', 'angel')
                ->where('cards_count', 3)
                ->etc()
            )
        )
    );
});

test('workspace home lists only the boards a member was added to and does not let them create boards', function () {
    $workspace = Workspace::factory()->forUser()->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member);
    $boardMemberIsOn = Board::factory()->for($workspace)->withMembers($member)->create();
    Board::factory()->for($workspace)->create();

    $response = $this->actingAs($member)->get(route('workspaces.home', $workspace));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('canCreateBoards', false)
        ->loadDeferredProps(fn (Assert $reload) => $reload
            ->has('boards', 1)
            ->where('boards.0.id', $boardMemberIsOn->id)
        )
    );
});

test('workspace home lists every board to the owner and lets them create boards', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Board::factory()->for($workspace)->count(2)->create();

    $response = $this->actingAs($owner)->get(route('workspaces.home', $workspace));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('canCreateBoards', true)
        ->loadDeferredProps(fn (Assert $reload) => $reload->has('boards', 2))
    );
});
