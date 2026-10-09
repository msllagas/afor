<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use App\Policies\Concerns\RequiresBoardAccess;
use Illuminate\Auth\Access\Response;

class BoardListPolicy
{
    use RequiresBoardAccess;

    /**
     * Determine whether the user can add a list to the board.
     */
    public function create(User $user, Board $board): Response
    {
        return $this->onEditableBoard($user, $board);
    }

    /**
     * Determine whether the user can update the list, including archiving and restoring it and reordering its cards.
     */
    public function update(User $user, BoardList $boardList): Response
    {
        return $this->onEditableBoard($user, $boardList->board);
    }
}
