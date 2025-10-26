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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('workspaces/{workspace}/home', [WorkspaceController::class, 'home'])
    ->name('workspaces.home')
    ->middleware(['auth', 'verified']);



Route::post('workspaces/{workspace}/boards', [BoardController::class, 'store'])
    ->name('workspaces.boards.store')
    ->scopeBindings()
    ->middleware(['auth', 'verified']);

Route::resource('boards', BoardController::class)
    ->only(['index', 'show'])
    ->middleware(['auth', 'verified']);

Route::patch('boards/{board}/board-lists/reorder', [BoardListController::class, 'reorder'])
    ->name('boards.board-lists.reorder')
    ->middleware(['auth', 'verified']);

Route::patch('board-lists/{board_list}/cards/reorder', [CardController::class, 'reorder'])
    ->name('board-lists.cards.reorder')
    ->middleware(['auth', 'verified']);

Route::scopeBindings()->group(function () {
    Route::resource('boards.board-lists', BoardListController::class)->middleware(['auth', 'verified']);
    Route::resource('board-lists.cards', CardController::class)->middleware(['auth', 'verified']);
});

require __DIR__ . '/invite.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
