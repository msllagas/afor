<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersBoardPage;
use App\Http\Requests\StoreBoardsRequest;
use App\Http\Requests\UpdateBoardsRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Board;
use App\Models\Workspace;
use App\Services\BoardService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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
                            ->active()
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
     * Remove the specified resource from storage.
     */
    public function destroy(Board $board)
    {
        Gate::authorize('delete', $board);

        // for now, only allow force deletion of archived boards
        if (!$board->isArchived()) {
            // if trash bins have been implemented, that's when soft deletion can be done, but for now
            // we'll just return errors
            return response()->json([
                'errors' => [
                    'board' => ['Only archived boards can be deleted.'],
                ],
            ], 422);
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

    public function unarchive(Board $board): Board
    {
        Gate::authorize('update', $board);

        return $this->boardService->unarchive($board);
    }

    public function archived(Workspace $workspace): Collection
    {
        Gate::authorize('view', $workspace);

        return $workspace->boards()
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
                    ->active()
                    ->withCount('cards'),
            ])
            ->latest('archived_at')
            ->get();
    }

    public function toggleFavorite(Workspace $workspace, Board $board): Board
    {
        Gate::authorize('view', $board);

        return $this->boardService->toggleFavorite($board, auth()->user());
    }
}
