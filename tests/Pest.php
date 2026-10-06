<?php

use App\Models\BoardList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser', 'Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Drag one element onto another with the mouse, moving in small steps the way a person does.
 * The page's drag() jumps straight to the target, which SortableJS's mouse fallback never takes for a drag.
 */
function dragWithMouse(AwaitableWebpage|PendingAwaitablePage $page, string $from, string $to): void
{
    $browserPage = $page->page();
    $browserPage->locator($from)->dragTo($browserPage->locator($to), ['steps' => 10]);
}

/**
 * A browser script that reads the names of the cards a board list shows, top to bottom.
 */
function cardNamesShownIn(BoardList $boardList): string
{
    return "Array.from(document.querySelectorAll('[data-list-id=\"{$boardList->id}\"] .board-card button > span:first-child'), (name) => name.textContent.trim())";
}

/**
 * A browser script that reads the names of the lists a board shows, left to right.
 */
function listNamesShown(): string
{
    return "Array.from(document.querySelectorAll('[data-list-id] [data-list-handle] h2'), (name) => name.textContent.trim())";
}
