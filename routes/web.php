<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardListController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
});
Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
});

Route::get('/terms-of-use', function () {
    return Inertia::render('TermsOfUse');
});

Route::get('/contact', function () {
    return Inertia::render('Contact');
});

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Workspaces
    |--------------------------------------------------------------------------
    */
    Route::prefix('workspaces')
        ->name('workspaces.')
        ->group(function () {

            Route::patch('/{workspace}', [WorkspaceController::class, 'update'])
                ->name('update');

            Route::get('/{workspace}/home', [WorkspaceController::class, 'home'])
                ->name('home');

            Route::get('/{workspace}/members', [WorkspaceController::class, 'members'])
                ->name('members');

            Route::delete('/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember'])
                ->name('members.user.destroy')
                ->scopeBindings();

            Route::get('/{workspace}/settings', [WorkspaceController::class, 'settings'])
                ->name('settings');

            Route::post('/{workspace}/boards', [BoardController::class, 'store'])
                ->name('boards.store');

        });

    /*
    |--------------------------------------------------------------------------
    | Boards
    |--------------------------------------------------------------------------
    */
    Route::prefix('boards')
        ->name('boards.')
        ->group(function () {

            Route::get('/', [BoardController::class, 'index'])
                ->name('index');

            Route::get('/{board}', [BoardController::class, 'show'])
                ->name('show');

            Route::patch('/{board}', [BoardController::class, 'update'])
                ->name('update');

            Route::patch('/{board}/board-lists/reorder', [BoardListController::class, 'reorder'])
                ->name('board-lists.reorder');
        });

    /*
    |--------------------------------------------------------------------------
    | Board Lists
    |--------------------------------------------------------------------------
    */
    Route::prefix('board-lists')
        ->name('board-lists.')
        ->group(function () {

            Route::patch('/{board_list}/cards/reorder', [CardController::class, 'reorder'])
                ->name('cards.reorder');
        });

    /*
    |--------------------------------------------------------------------------
    | Nested Resources
    |--------------------------------------------------------------------------
    */
    Route::scopeBindings()->group(function () {

        Route::resource('boards.board-lists', BoardListController::class);

        Route::resource('board-lists.cards', CardController::class);
    });

});

require __DIR__.'/invite.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
