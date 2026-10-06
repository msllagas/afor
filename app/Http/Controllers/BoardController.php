<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersBoardPage;
use App\Http\Requests\StoreBoardsRequest;
use App\Http\Requests\UpdateBoardsRequest;
use App\Http\Resources\BoardListResource;
use App\Http\Resources\BoardResource;
use App\Http\Resources\CardResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Board;
use App\Models\Card;
use App\Models\Workspace;
use App\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    use RendersBoardPage;

    public function __construct(
        private readonly BoardService $boardService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $user = auth()->user();

        [$ownedWorkspaces, $sharedWorkspaces] = Workspace::query()
            ->select('id', 'name', 'owner_id')
            ->where('owner_id', $user->id)
            ->orWhereHas('users', fn ($query) => $query->whereKey($user->id))
            ->with([
                'logoFile',
                'boards' => fn ($query) => $query
                    ->select('id', 'name', 'workspace_id', 'created_at')
                    ->unarchived()
                    ->visibleTo($user)
                    ->withExists([
                        'favoritedByUsers as is_favorited' => fn ($query) => $query->whereKey($user->id),
                    ])
                    ->with([
                        'boardLists' => fn ($query) => $query
                            ->select('id', 'board_id', 'color', 'order')
                            ->unarchived()
                            ->withCount('cards'),
                    ])
                    ->oldest(),
            ])
            ->get()
            ->partition(fn (Workspace $workspace) => $workspace->owner_id === $user->id);

        return Inertia::render('boards/Index', [
            'ownedWorkspaces'  => WorkspaceResource::collection($ownedWorkspaces->values())->resolve(),
            'sharedWorkspaces' => WorkspaceResource::collection($sharedWorkspaces->values())->resolve(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBoardsRequest $request, Workspace $workspace): RedirectResponse
    {
        $board = $this->boardService->create($request->validated(), $workspace);

        return to_route('boards.show', [
            'board' => $board,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Board $board): Response
    {
        Gate::authorize('view', $board);

        return $this->renderBoardPage($board, auth()->user());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBoardsRequest $request, Board $board): RedirectResponse
    {
        $board->update($request->validated());

        return back();
    }

    /**
     * Permanently delete an archived board. Boards are archived first, so nothing is deleted in one step.
     *
     * @throws ValidationException when the board is not archived
     */
    public function destroy(Board $board): HttpResponse
    {
        Gate::authorize('delete', $board);

        if (!$board->isArchived()) {
            throw ValidationException::withMessages(['board' => 'Only archived boards can be deleted.']);
        }

        $board->forceDelete();

        return response()->noContent();
    }

    public function archive(Board $board): RedirectResponse
    {
        Gate::authorize('update', $board);

        $this->boardService->archive($board, auth()->user());

        return back();
    }

    public function unarchive(Board $board): BoardResource
    {
        Gate::authorize('update', $board);

        return new BoardResource($this->boardService->unarchive($board));
    }

    public function archived(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('view', $workspace);

        return BoardResource::collection($workspace->boards()
            ->select('id', 'name', 'workspace_id', 'created_at', 'archived_at', 'archived_by')
            ->archived()
            ->visibleTo(auth()->user())
            ->withExists([
                'favoritedByUsers as is_favorited' => fn ($query) => $query->whereKey(auth()->id()),
            ])
            ->with([
                'archiver:id,name',
                'boardLists' => fn ($query) => $query
                    ->select('id', 'board_id', 'color', 'order')
                    ->unarchived()
                    ->withCount('cards'),
            ])
            ->latest('archived_at')
            ->get());
    }

    /**
     * The board's archived lists and the deleted cards of its other lists, most recent first, so they can be restored.
     * Cards deleted from an archived list come back into view once the list is restored.
     */
    public function archivedItems(Board $board): JsonResponse
    {
        Gate::authorize('view', $board);

        $unarchivedListIds = $board->boardLists()->reorder()->unarchived()->select('id');

        return response()->json([
            'board_lists' => BoardListResource::collection($board->boardLists()
                ->reorder()
                ->select('id', 'name', 'color', 'board_id', 'archived_at', 'archived_by')
                ->archived()
                ->with('archiver:id,name')
                ->withCount('cards')
                ->latest('archived_at')
                ->get()),
            'cards' => CardResource::collection(Card::onlyTrashed()
                ->select('id', 'name', 'board_list_id', 'deleted_at')
                ->whereIn('board_list_id', $unarchivedListIds)
                ->with('boardList:id,name,color')
                ->latest('deleted_at')
                ->get()),
        ]);
    }

    public function toggleFavorite(Workspace $workspace, Board $board): BoardResource
    {
        Gate::authorize('view', $board);

        return new BoardResource($this->boardService->toggleFavorite($board, auth()->user()));
    }
}
