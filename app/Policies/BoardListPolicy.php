<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use App\Policies\Concerns\RequiresWorkspaceMembership;
use Illuminate\Auth\Access\Response;

class BoardListPolicy
{
    use RequiresWorkspaceMembership;

    /**
     * Determine whether the user can add a list to the board.
     */
    public function create(User $user, Board $board): Response
    {
        return $this->memberOf($user, $board->workspace);
    }

    /**
     * Determine whether the user can update the list, including archiving it and reordering its cards.
     */
    public function update(User $user, BoardList $boardList): Response
    {
        return $this->memberOf($user, $boardList->board->workspace);
    }
}
