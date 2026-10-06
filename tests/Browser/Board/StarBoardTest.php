<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

it('stars the board and lists it under Starred in the sidebar', function () {
    $user = User::factory()->create();
    $board = Board::factory()->for(Workspace::factory()->forUser($user))->create();
    $starredLink = "[data-sidebar=\"content\"] a[href$=\"/boards/{$board->id}\"]";

    $this->actingAs($user);
    $page = visit(route('boards.show', $board));

    $page->assertSee('Star a board to keep it here.')
        ->click('[aria-label="Star board"]')
        ->assertPresent('[aria-label="Unstar board"]')
        ->assertPresent($starredLink)
        ->refresh()
        ->assertPresent('[aria-label="Unstar board"]')
        ->assertPresent($starredLink)
        ->assertNoJavaScriptErrors();
});
