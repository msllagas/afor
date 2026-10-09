<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create(['name' => 'Ada Lovelace']);
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()
        ->for($this->workspace)
        ->withMembers($this->member)
        ->archived($this->member)
        ->create(['name' => 'Camping trip']);
    $this->backlog = BoardList::factory()->for($this->board)->create(['name' => 'Backlog', 'order' => 0]);
    $this->card = Card::factory()->for($this->backlog)->create(['name' => 'Pack the tent', 'description' => '<p>Two poles</p>']);
});

it('shows an archived board read-only until the owner restores it', function () {
    $this->actingAs($this->owner);

    $page = visit(route('boards.show', $this->board));

    $page->assertSee('This board is archived')
        ->assertNotPresent('[aria-label="Board actions"]')
        ->assertNotPresent('[aria-label="Star board"]')
        ->assertNotPresent('[aria-label="Actions for list Backlog"]')
        ->assertNotPresent('[title="Rename board"]')
        ->assertDontSee('Add list')
        ->assertDontSee('Add a card')
        ->click('Pack the tent')
        ->assertSee('Two poles')
        ->assertPresent('textarea[aria-label="Card title"][readonly]')
        ->assertPresent('[aria-label="Description"][contenteditable="false"]')
        ->assertNotPresent('[aria-label="Card actions"]')
        ->assertNotPresent('[aria-label="Text formatting"]')
        ->keys('[role="dialog"]', 'Escape')
        ->click('Restore board')
        ->assertSee('Restored “Camping trip”')
        ->assertDontSee('This board is archived')
        ->assertPresent('[aria-label="Board actions"]')
        ->assertPresent('[aria-label="Actions for list Backlog"]')
        ->assertNoJavaScriptErrors();

    expect($this->board->fresh()->archived_at)->toBeNull();
});

it('lets the owner delete an archived board from its banner', function () {
    $this->actingAs($this->owner);

    $page = visit(route('boards.show', $this->board));

    $page->click('section[aria-labelledby="archived-board-title"] button:has-text("Delete board")')
        ->assertSee('Delete permanently?')
        ->click('[role="group"] button:has-text("Delete board")')
        ->assertSee('Deleted “Camping trip”')
        ->assertPathIs("/workspaces/{$this->workspace->id}/home")
        ->assertNoJavaScriptErrors();

    $this->assertModelMissing($this->board);
});

it('opens an archived board from the archived boards of the workspace home', function () {
    $this->actingAs($this->owner);

    $page = visit(route('workspaces.home', $this->workspace));

    $page->click('button:has-text("Archived")')
        ->click('[data-board-link]')
        ->assertPathIs("/boards/{$this->board->id}")
        ->assertSee('This board is archived')
        ->assertNoJavaScriptErrors();
});

it('keeps archived boards out of sight of members, who can still open and leave theirs by its address', function () {
    $this->actingAs($this->member);

    $home = visit(route('workspaces.home', $this->workspace));

    $home->assertDontSee('Camping trip')
        ->assertNotPresent('button:has-text("Archived")');

    $board = visit(route('boards.show', $this->board));

    $board->assertSee('This board is archived')
        ->assertDontSee('Restore board')
        ->assertDontSee('Delete board')
        ->assertNotPresent('[aria-label="Board actions"]')
        ->click('section[aria-labelledby="archived-board-title"] button:has-text("Leave board")')
        ->click('[role="dialog"] button:has-text("Leave board")')
        ->assertSee('You left “Camping trip”')
        ->assertPathIs("/workspaces/{$this->workspace->id}/home")
        ->assertNoJavaScriptErrors();

    expect($this->board->members()->whereKey($this->member->id)->exists())->toBeFalse();
});

it('offers archiving a board to the workspace owner only', function () {
    $this->board->update(['archived_at' => null, 'archived_by' => null]);

    $this->actingAs($this->member);

    visit(route('boards.show', $this->board))
        ->click('[aria-label="Board actions"]')
        ->assertSee('Archived items')
        ->assertDontSee('Archive board')
        ->assertNoJavaScriptErrors();

    $this->actingAs($this->owner);

    visit(route('boards.show', $this->board))
        ->click('[aria-label="Board actions"]')
        ->assertSee('Archive board')
        ->assertNoJavaScriptErrors();
});
