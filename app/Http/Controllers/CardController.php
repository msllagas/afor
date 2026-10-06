<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersBoardPage;
use App\Http\Requests\ReorderCardsRequest;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    /**
     * Bring back a deleted card, such as from the undo offered right after deleting it.
     */
    public function restore(BoardList $boardList, Card $card): RedirectResponse
    {
        Gate::authorize('restore', $card);

        $card->restore();

        return back();
    }

    /**
     * Save the order of the list's cards. Cards of other lists in the payload are left alone.
     */
    public function reorder(ReorderCardsRequest $request, BoardList $boardList): RedirectResponse
    {
        DB::transaction(function () use ($request, $boardList) {
            foreach ($request->validated('cards') as $card) {
                Card::query()->where('id', $card['id'])
                    ->where('board_list_id', $boardList->id)
                    ->update(['order' => $card['order']]);
            }
        });

        return back();
    }
}
