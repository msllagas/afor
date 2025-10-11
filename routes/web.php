<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\CardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('boards', BoardController::class)
    ->middleware(['auth', 'verified']);

Route::patch('board-lists/{board_list}/cards/reorder', [CardController::class, 'reorder'])
    ->name('board-lists.cards.reorder')
    ->middleware(['auth', 'verified']);

Route::resource('board-lists.cards', CardController::class)->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
