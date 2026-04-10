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

            Route::get('/{workspace}/home', [WorkspaceController::class, 'home'])
                ->name('home');

            Route::get('/{workspace}/members', [WorkspaceController::class, 'members'])
                ->name('members');

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
