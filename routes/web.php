<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardListController;
use App\Http\Controllers\BoardMemberController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');
Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

Route::get('/terms-of-use', function () {
    return Inertia::render('TermsOfUse');
})->name('terms-of-use');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

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

            Route::delete('/{workspace}', [WorkspaceController::class, 'destroy'])
                ->name('destroy');

            Route::get('/{workspace}/home', [WorkspaceController::class, 'home'])
                ->name('home');

            Route::get('/{workspace}/members', [WorkspaceController::class, 'members'])
                ->name('members');

            Route::delete('/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember'])
                ->name('members.user.destroy')
                ->scopeBindings();

            Route::delete('/{workspace}/membership', [WorkspaceController::class, 'leave'])
                ->name('leave');

            Route::post('/{workspace}/invite-link/reset', [WorkspaceInvitationController::class, 'reset'])
                ->name('invite-link.reset');

            Route::get('/{workspace}/settings', [WorkspaceController::class, 'settings'])
                ->name('settings');

            Route::post('/{workspace}/boards', [BoardController::class, 'store'])
                ->name('boards.store');

            Route::get('/{workspace}/boards/archived', [BoardController::class, 'archived'])
                ->name('boards.archived');

            Route::post('/{workspace}/boards/{board}/favorite', [BoardController::class, 'toggleFavorite'])
                ->name('boards.favorite')
                ->scopeBindings();

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

            Route::delete('/{board}', [BoardController::class, 'destroy'])
                ->name('destroy');

            Route::patch('/{board}/archive', [BoardController::class, 'archive'])
                ->name('archive');

            Route::patch('/{board}/unarchive', [BoardController::class, 'unarchive'])
                ->name('unarchive');

            Route::patch('/{board}/board-lists/reorder', [BoardListController::class, 'reorder'])
                ->name('board-lists.reorder');

            Route::post('/{board}/members', [BoardMemberController::class, 'store'])
                ->name('members.store');

            Route::delete('/{board}/members/{member}', [BoardMemberController::class, 'destroy'])
                ->name('members.destroy')
                ->scopeBindings();

            Route::delete('/{board}/membership', [BoardMemberController::class, 'leave'])
                ->name('leave');
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

        Route::resource('boards.board-lists', BoardListController::class)
            ->only(['store', 'update']);

        Route::resource('board-lists.cards', CardController::class)
            ->only(['store', 'show', 'update', 'destroy']);
    });

});

require __DIR__.'/invite.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
