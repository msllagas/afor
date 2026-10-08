<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;

it('saves a description exactly as the editor wrote it', function () {
    $user = User::factory()->create();
    $list = BoardList::factory()->for(Board::factory()->for(Workspace::factory()->forUser($user)))->create();
    $card = Card::factory()->for($list)->create(['description' => null]);
    $this->actingAs($user);
    $editor = '.ProseMirror[aria-label="Description"]';

    $page = visit(route('board-lists.cards.show', [$list, $card]))
        ->type($editor, "Don't forget the \"big\" tent & the <3 stove")
        ->keys($editor, ['Control+a', 'Control+b'])
        ->click('[aria-label="Highlight"]')
        ->click('[aria-label="Blue highlight"]')
        // Leaving the editor saves the description.
        ->click('[aria-label="Card title"]')
        ->waitForEvent('networkidle');

    $editorHtml = $page->script("document.querySelector('{$editor}').editor.getHTML()");

    // The sanitizer must keep whole what the editor wrote. If it doesn't, the two have drifted apart,
    // and opening a card would save its description again for nothing.
    expect($card->refresh()->description)
        ->toBe($editorHtml)
        ->toContain("Don't forget", '<strong>', 'class="list-blue"');
    $page->assertNoJavaScriptErrors();
});
