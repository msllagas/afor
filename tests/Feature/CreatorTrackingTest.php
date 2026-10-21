<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
    $this->boardList = BoardList::factory()->for($this->board)->create();
});

test('boards, lists and cards record who created them', function () {
    $this->actingAs($this->owner)
        ->post(route('workspaces.boards.store', $this->workspace), ['name' => 'Camping trip']);
    $this->actingAs($this->member)
        ->post(route('boards.board-lists.store', $this->board), ['name' => 'Gear']);
    $this->post(route('board-lists.cards.store', $this->boardList), ['name' => 'Pack the tent']);

    expect(Board::query()->where('name', 'Camping trip')->value('created_by'))->toBe($this->owner->id)
        ->and(BoardList::query()->where('name', 'Gear')->value('created_by'))->toBe($this->member->id)
        ->and(Card::query()->where('name', 'Pack the tent')->value('created_by'))->toBe($this->member->id);
});

test('the starter board of a new account is recorded as created by that account', function () {
    $this->post(route('register.store'), [
        'name'                  => 'Ada Lovelace',
        'email'                 => 'ada@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);
    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();
    $board = Board::query()->whereIn('workspace_id', $user->ownedWorkspaces()->select('id'))->firstOrFail();

    expect($board->created_by)->toBe($user->id)
        ->and($board->boardLists()->pluck('created_by')->unique()->all())->toBe([$user->id])
        ->and(Card::query()->whereIn('board_list_id', $board->boardLists()->select('id'))->pluck('created_by')->unique()->all())
        ->toBe([$user->id]);
});

test('what a member created stays on the board after they delete their account', function () {
    $card = Card::factory()->for($this->boardList)->create(['created_by' => $this->member->id]);

    $this->member->delete();

    expect($card->fresh()->created_by)->toBeNull();
});

test('who created the board, its lists and cards is not sent to the browser', function () {
    $this->board->update(['created_by' => $this->owner->id]);
    $this->boardList->update(['created_by' => $this->owner->id]);

    $this->actingAs($this->member)
        ->get(route('boards.show', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('board.created_by')
            ->missing('board.board_lists.0.created_by')
        );
});
