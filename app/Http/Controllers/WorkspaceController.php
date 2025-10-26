<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Inertia\Inertia;

class WorkspaceController extends Controller
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
    public function store(StoreWorkspaceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Workspace $workspace)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workspace $workspace)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workspace $workspace)
    {
        //
    }

    public function home(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->user_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOwner && !$isMember) {
            return redirect()->route('dashboard');
        }

        $workspace->load('boards');

        return Inertia::render('workspaces/Home', [
            'workspace' => $workspace,
        ]);
    }

    public function members(Workspace $workspace)
    {
        $user = auth()->user();

        $isOwner = $workspace->user_id === $user->id;

        $isMember = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOwner && !$isMember) {
            return redirect()->route('dashboard');
        }

        $owner = $workspace->user()
            ->select(['id', 'name'])
            ->first();

        $members = $workspace->users()
            ->select('users.id', 'users.name')
            ->get();

        return Inertia::render('workspaces/Member', [
            'workspace' => $workspace,
            'owner' => $owner,
            'members' => $members,
        ]);
    }
}
