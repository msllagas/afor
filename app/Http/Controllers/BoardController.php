<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardsRequest;
use App\Http\Requests\UpdateBoardsRequest;
use App\Models\Board;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $boards = Board::query()
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('boards/Index', [
            'boards' => $boards
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBoardsRequest $request): RedirectResponse
    {
        $board = Board::query()->create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        \Artisan::call('board:seed', ['board' => $board->id]);

        $board->load('boardLists.cards');

        return to_route('boards.show', [
            'board' => $board,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Board $board): Response
    {
        $board->load('boardLists.cards');

        return Inertia::render('boards/Show', [
            'board' => $board,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Board $boards)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBoardsRequest $request, Board $boards)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Board $boards)
    {
        //
    }
}
