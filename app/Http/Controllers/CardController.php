<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersBoardPage;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class CardController extends Controller
{
    use RendersBoardPage;

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
                'order'         => $nextOrder,
            ],
        ));

        return back();
    }

    /**
     * Display the board with the card open.
     */
    public function show(BoardList $boardList, Card $card): Response
    {
        Gate::authorize('view', $card);

        return $this->renderBoardPage($boardList->board, auth()->user(), $card);
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
    public function destroy(BoardList $boardList, Card $card): RedirectResponse
    {
        Gate::authorize('delete', $card);

        $card->delete();

        return back();
    }

    public function reorder(Request $request, BoardList $boardList): RedirectResponse
    {
        Gate::authorize('update', $boardList);

        $cards = $request->input('cards', []);

        foreach ($cards as $cardData) {
            Card::query()->where('id', $cardData['id'])
                ->where('board_list_id', $boardList->id)
                ->update(['order' => $cardData['order']]);
        }

        return back();
    }
}
