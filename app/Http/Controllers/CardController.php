<?php

namespace App\Http\Controllers;

use App\Events\BoardChanged;
use App\Http\Controllers\Concerns\RendersBoardPage;
use App\Http\Requests\ReorderCardsRequest;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\BoardList;
use App\Models\Card;
use App\Services\CardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class CardController extends Controller
{
    use RendersBoardPage;

    public function __construct(
        private readonly CardService $cardService,
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCardRequest $request, BoardList $boardList): RedirectResponse
    {
        $this->cardService->create($boardList, $request->validated());

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
    public function update(UpdateCardRequest $request, BoardList $boardList, Card $card): RedirectResponse
    {
        $hadDescription = $card->description !== null;

        $card->update($request->validated());

        if ($card->wasChanged(['name', 'is_checked', 'board_list_id', 'order']) || $hadDescription !== ($card->description !== null)) {
            BoardChanged::dispatch($boardList->board);
        }

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoardList $boardList, Card $card): RedirectResponse
    {
        Gate::authorize('delete', $card);

        $card->delete();

        BoardChanged::dispatch($boardList->board);

        return back();
    }

    /**
     * Permanently delete an archived card. Cards are archived first, so nothing is deleted in one step.
     *
     * @throws ValidationException when the card is not archived
     */
    public function forceDelete(BoardList $boardList, Card $card): HttpResponse
    {
        Gate::authorize('forceDelete', $card);

        if (!$card->trashed()) {
            throw ValidationException::withMessages(['card' => 'Only archived cards can be deleted.']);
        }

        $card->forceDelete();

        BoardChanged::dispatch($boardList->board);

        return response()->noContent();
    }

    /**
     * Bring back a deleted card, such as from the undo offered right after deleting it.
     */
    public function restore(BoardList $boardList, Card $card): RedirectResponse
    {
        Gate::authorize('restore', $card);

        $card->restore();

        BoardChanged::dispatch($boardList->board);

        return back();
    }

    /**
     * Save the order of the list's cards. Cards of other lists in the payload are left alone.
     */
    public function reorder(ReorderCardsRequest $request, BoardList $boardList): RedirectResponse
    {
        $this->cardService->reorder($boardList, $request->validated('cards'));

        return back();
    }
}
