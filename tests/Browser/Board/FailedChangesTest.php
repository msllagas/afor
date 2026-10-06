<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

/**
 * Changes show on the board straight away. These cover what happens when the server then turns one down.
 */
it('drops a card someone else deleted when renaming it fails', function () {
    $user = User::factory()->create();
    $board = Board::factory()->for(Workspace::factory()->forUser($user))->create();
    $list = BoardList::factory()->for($board)->create();
    $card = Card::factory()->for($list)->create(['name' => 'Ship it']);

    $this->actingAs($user);
    $page = visit(route('board-lists.cards.show', [$list, $card]))
        ->assertValue('[aria-label="Card title"]', 'Ship it');

    $card->delete();

    $page->type('[aria-label="Card title"]', 'Ship it today')
        ->keys('[aria-label="Card title"]', 'Enter')
        ->assertSee('Could not rename the card.')
        ->assertPathIs("/boards/{$board->id}")
        ->assertNotPresent("[data-card-id=\"{$card->id}\"]")
        ->assertNoJavaScriptErrors();

    expect($card->fresh()->name)->toBe('Ship it');
});

it('sends someone taken off the board to their workspace when their next change fails', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member);
    $board = Board::factory()->for($workspace)->withMembers($member)->create();
    $backlog = BoardList::factory()->for($board)->create(['name' => 'Backlog', 'order' => 0]);
    BoardList::factory()->for($board)->create(['name' => 'Doing', 'order' => 1]);

    $this->actingAs($member);
    $page = visit(route('boards.show', $board))->assertSee('Backlog');

    $board->members()->detach($member);

    $page->click('[aria-label="Actions for list Backlog"]')
        ->click('Move list right')
        ->assertSee('You no longer have access to this board')
        ->assertPathIs("/workspaces/{$workspace->id}/home")
        ->assertNoJavaScriptErrors();

    expect($backlog->fresh()->order)->toBe(0);
});
