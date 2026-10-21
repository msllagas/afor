<?php

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoardService
{
    public function __construct(
        private readonly UsageLimits $usageLimits
    ) {}

    /**
     * The workspace row is locked while its boards are counted, so two requests can't both take the last free slot.
     *
     * @throws ValidationException when the workspace has reached its board limit
     */
    public function create(array $data, Workspace $workspace, User $creator): Board
    {
        $board = DB::transaction(function () use ($data, $workspace, $creator) {
            Workspace::query()->whereKey($workspace->id)->lockForUpdate()->first();

            $this->usageLimits->ensureRoomForBoard($workspace);

            return Board::create([
                ...$data,
                'workspace_id' => $workspace->id,
                'created_by'   => $creator->id,
            ]);
        });

        BoardChanged::dispatch($board);

        return $board;
    }

    public function archive(Board $board, User $archiver): Board
    {
        $board->update([
            'archived_by' => $archiver->id,
            'archived_at' => now(),
        ]);

        BoardChanged::dispatch($board);

        return $board;
    }

    public function unarchive(Board $board): Board
    {
        $board->update([
            'archived_by' => null,
            'archived_at' => null,
        ]);

        BoardChanged::dispatch($board);

        return $board;
    }

    public function addMember(Board $board, User $user): void
    {
        $board->members()->attach($user->id);

        BoardChanged::dispatch($board);
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

        BoardChanged::dispatch($board);
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
