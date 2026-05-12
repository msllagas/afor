<?php

namespace App\Http\Controllers;

use App\Enums\BoardListColor;
use App\Http\Requests\StoreBoardsRequest;
use App\Http\Requests\UpdateBoardsRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Board;
use App\Models\Workspace;
use App\Services\BoardService;
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
        $user = auth()->user();
        $ownedWorkspaces = $user->ownedWorkspaces()
            ->select('id', 'name')
            ->with([
                'boards:id,name,workspace_id',
                'logoFile',
            ])
            ->get();

        $sharedWorkspaces = $user->sharedWorkspaces()
            ->select('workspaces.id', 'workspaces.name')
            ->with([
                'boards:id,name,workspace_id',
                'logoFile',
            ])
            ->get();

        return Inertia::render('boards/Index', [
            'ownedWorkspaces'  => WorkspaceResource::collection($ownedWorkspaces)->resolve(),
            'sharedWorkspaces' => WorkspaceResource::collection($sharedWorkspaces)->resolve(),
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
    public function store(StoreBoardsRequest $request, Workspace $workspace): RedirectResponse
    {
        $board = app(BoardService::class)->create($request->validated(), $workspace);

        return to_route('boards.show', [
            'board' => $board,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Board $board): Response
    {
        $board->load([
            'boardLists' => function ($query) {
                $query->with('cards')
                    ->active();
            },
        ]);

        return Inertia::render('boards/Show', [
            'board'        => $board,
            'selectedCard' => null,
            'colors'       => Inertia::once(fn () => BoardListColor::cases()),
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
    public function update(UpdateBoardsRequest $request, Board $board): RedirectResponse
    {
        $board->update($request->validated());

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Board $boards)
    {
        //
    }
}
