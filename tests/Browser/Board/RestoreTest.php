<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->for(Workspace::factory()->forUser($this->user))->create();
    $this->backlog = BoardList::factory()->for($this->board)->create(['name' => 'Backlog', 'order' => 0]);

    foreach (['First', 'Second', 'Third'] as $order => $name) {
        Card::factory()->for($this->backlog)->create(['name' => $name, 'order' => $order, 'description' => null]);
    }

    $this->actingAs($this->user);
});

it('puts a deleted card back in its place when the delete is undone', function () {
    $second = Card::firstWhere('name', 'Second');

    $page = visit(route('board-lists.cards.show', [$this->backlog, $second]));

    $page->click('[aria-label="Card actions"]')
        ->click('Delete card')
        ->click('[role="alert"] button:has-text("Delete card")')
        ->assertSee('Deleted “Second”')
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Third'])
        ->click('Undo')
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Second', 'Third'])
        ->refresh()
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Second', 'Third'])
        ->assertNoJavaScriptErrors();

    expect($second->fresh()->trashed())->toBeFalse();
});

it('restores an archived list and a deleted card from archived items', function () {
    $ideas = BoardList::factory()->for($this->board)->archived($this->user)->create(['name' => 'Ideas', 'order' => 1]);
    $oldCard = Card::factory()->for($this->backlog)->trashed()->create(['name' => 'Fourth', 'order' => 3]);

    $page = visit(route('boards.show', $this->board));

    $page->assertScript(listNamesShown(), ['Backlog'])
        ->click('[aria-label="Board actions"]')
        ->click('Archived items')
        ->click("[data-restore=\"{$ideas->id}\"]")
        ->click("[data-restore=\"{$oldCard->id}\"]")
        ->assertSee('Nothing archived')
        ->assertScript(listNamesShown(), ['Backlog', 'Ideas'])
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Second', 'Third', 'Fourth'])
        ->refresh()
        ->assertScript(listNamesShown(), ['Backlog', 'Ideas'])
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Second', 'Third', 'Fourth'])
        ->assertNoJavaScriptErrors();
});
