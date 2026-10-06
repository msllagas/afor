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
    $this->doing = BoardList::factory()->for($this->board)->create(['name' => 'Doing', 'order' => 1]);

    foreach (['First', 'Second', 'Third'] as $order => $name) {
        Card::factory()->for($this->backlog)->create(['name' => $name, 'order' => $order, 'description' => null]);
    }

    Card::factory()->for($this->doing)->create(['name' => 'Started', 'order' => 0, 'description' => null]);

    $this->actingAs($this->user);
});

it('keeps a card dragged into another list there', function () {
    $second = Card::firstWhere('name', 'Second');
    $started = Card::firstWhere('name', 'Started');

    $page = visit(route('boards.show', $this->board));

    dragWithMouse($page, "[data-card-id=\"{$second->id}\"]", "[data-card-id=\"{$started->id}\"]");

    $page->assertScript(cardNamesShownIn($this->backlog), ['First', 'Third'])
        ->assertScript(cardNamesShownIn($this->doing), ['Started', 'Second'])
        ->refresh()
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Third'])
        ->assertScript(cardNamesShownIn($this->doing), ['Started', 'Second'])
        ->assertNoJavaScriptErrors();
});

it('moves a card to the top and bottom of its list from the card menu', function () {
    $third = Card::firstWhere('name', 'Third');

    $page = visit(route('board-lists.cards.show', [$this->backlog, $third]));

    $page->click('[aria-label="Card actions"]')
        ->assertSee('Position 3 of 3')
        ->click('Move to top')
        ->assertSee('Position 1 of 3')
        ->assertScript(cardNamesShownIn($this->backlog), ['Third', 'First', 'Second'])
        ->assertSee('Moved card Third to position 1 of 3 in Backlog.')
        ->refresh()
        ->assertScript(cardNamesShownIn($this->backlog), ['Third', 'First', 'Second'])
        ->click('[aria-label="Card actions"]')
        ->click('Move to bottom')
        ->assertSee('Position 3 of 3')
        ->refresh()
        ->assertScript(cardNamesShownIn($this->backlog), ['First', 'Second', 'Third'])
        ->assertNoJavaScriptErrors();
});

it('moves a list from its menu', function () {
    $page = visit(route('boards.show', $this->board));

    $page->click('[aria-label="Actions for list Backlog"]')
        ->click('Move list right')
        ->assertScript(listNamesShown(), ['Doing', 'Backlog'])
        ->refresh()
        ->assertScript(listNamesShown(), ['Doing', 'Backlog'])
        ->assertNoJavaScriptErrors();
});
