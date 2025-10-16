<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreCardRequest $request, BoardList $boardList): RedirectResponse
    {
        $nextOrder = $boardList->cards()->max('order');
        $nextOrder = is_null($nextOrder) ? 0 : $nextOrder + 1;

        Card::query()->create(array_merge(
            $request->validated(),
            [
                'board_list_id' => $boardList->id,
                'order' => $nextOrder,
            ],
        ));

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Card $card)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BoardList $boardList, Card $card): Response
    {
        $board = $boardList->board;
        $board->load('boardLists.cards');

        return Inertia::render('boards/Show', [
            'board' => $boardList->board,
            'card' => $card,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCardRequest $request, BoardList $boardList, Card $card)
    {
        $card->update($request->validated());
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Card $card)
    {
        //
    }


    public function reorder(Request $request, BoardList $boardList): RedirectResponse
    {
        $cards = $request->input('cards', []);

        foreach ($cards as $cardData) {
            Card::query()->where('id', $cardData['id'])
                ->where('board_list_id', $boardList->id)
                ->update(['order' => $cardData['order']]);
        }
        return back();
    }
}
