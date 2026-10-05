<?php

namespace App\Policies;

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Policies\Concerns\RequiresBoardAccess;
use Illuminate\Auth\Access\Response;

class CardPolicy
{
    use RequiresBoardAccess;

    /**
     * Determine whether the user can open the card.
     */
    public function view(User $user, Card $card): Response
    {
        return $this->onBoard($user, $card->boardList->board);
    }

    /**
     * Determine whether the user can add a card to the list.
     */
    public function create(User $user, BoardList $boardList): Response
    {
        return $this->onBoard($user, $boardList->board);
    }

    /**
     * Determine whether the user can update the card, including moving it to another list.
     */
    public function update(User $user, Card $card): Response
    {
        return $this->onBoard($user, $card->boardList->board);
    }

    /**
     * Determine whether the user can delete the card.
     */
    public function delete(User $user, Card $card): Response
    {
        return $this->onBoard($user, $card->boardList->board);
    }
}
