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
     * Determine whether the user can update the list, including reordering its cards.
     */
    public function update(User $user, BoardList $boardList): Response
    {
        return $this->onEditableBoard($user, $boardList->board);
    }

    /**
     * Determine whether the user can archive the list or restore it. Archiving can be undone, so anyone on the board
     * can; the archived list records who did it.
     */
    public function archive(User $user, BoardList $boardList): Response
    {
        return $this->onEditableBoard($user, $boardList->board);
    }

    /**
     * Determine whether the user can permanently delete the list and its cards. Only the workspace owner can.
     */
    public function delete(User $user, BoardList $boardList): Response
    {
        return $this->ownerOnEditableBoard($user, $boardList->board, 'Only the workspace owner can delete lists.');
    }
}
