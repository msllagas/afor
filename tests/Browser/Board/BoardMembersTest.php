<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create(['name' => 'Launch Checklist']);
});

it('lets a member leave the board from the bottom of its board actions', function () {
    $this->actingAs($this->member);

    visit(route('boards.show', $this->board))
        ->click('[aria-label="Board actions"]')
        ->click('[role="menuitem"]:has-text("Leave board")')
        ->assertSee('Leave Launch Checklist?')
        ->click('[role="dialog"] button:has-text("Leave board")')
        ->assertSee('You left “Launch Checklist”')
        ->assertPathIs("/workspaces/{$this->workspace->id}/home")
        ->assertNoJavaScriptErrors();

    expect($this->board->members()->whereKey($this->member->id)->exists())->toBeFalse();
});

it('does not offer the owner to leave a board of their workspace', function () {
    $this->actingAs($this->owner);

    visit(route('boards.show', $this->board))
        ->click('[aria-label="Board actions"]')
        ->assertSee('Archive board')
        ->assertDontSee('Leave board')
        ->assertNoJavaScriptErrors();
});
