<?php

namespace App\Services;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BoardService
{
    public function create(array $data, Workspace $workspace): Board
    {
        return Board::create([
            ...$data,
            'workspace_id' => $workspace->id,
        ]);
    }

    public function archive(Board $board, User $archiver): Board
    {
        $board->update([
            'archived_by' => $archiver->id,
            'archived_at' => now(),
        ]);

        return $board;
    }

    public function unarchive(Board $board): Board
    {
        $board->update([
            'archived_by' => null,
            'archived_at' => null,
        ]);

        return $board;
    }

    public function addMember(Board $board, User $user): void
    {
        $board->members()->attach($user->id);
    }

    /**
     * Take the user off the board along with their star on it. They stay in the workspace.
     */
    public function removeMember(Board $board, User $user): void
    {
        DB::transaction(function () use ($board, $user) {
            $board->members()->detach($user->id);

            $user->favoriteBoards()->detach($board->id);
        });
    }

    public function toggleFavorite(Board $board, User $user): Board
    {
        $user->favoriteBoards()->toggle($board->id);

        $board->loadExists([
            'favoritedByUsers as is_favorited' => fn ($query) => $query->where('user_id', $user->id),
        ]);

        return $board;
    }
}
