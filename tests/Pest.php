<?php

use App\Models\BoardList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Vite;
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
| Browser tests load the built assets, as CI does, even while the Vite dev server runs. Pages from the dev server
| fetch every module on its own, around a hundred per page, and Chromium aborts whatever is still loading when the
| machine's network changes (Docker starting or stopping a container is enough), which leaves a blank page.
*/
pest()->in('Browser')->beforeEach(function () {
    buildAssetsIfStale();

    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
});

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
 * Build the assets once per run when the frontend changed since the last build, so browser tests never run old code.
 */
function buildAssetsIfStale(): void
{
    static $isChecked = false;

    if ($isChecked) {
        return;
    }

    $isChecked = true;
    $manifest = public_path('build/manifest.json');
    $sources = [
        ...File::allFiles(resource_path('js')),
        ...File::allFiles(resource_path('css')),
        new SplFileInfo(base_path('vite.config.ts')),
        new SplFileInfo(base_path('package-lock.json')),
    ];
    $newestSource = max(array_map(fn (SplFileInfo $file) => $file->getMTime(), $sources));

    if (File::exists($manifest) && File::lastModified($manifest) >= $newestSource) {
        return;
    }

    Process::path(base_path())->timeout(300)->run('npm run build')->throw();
}

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
