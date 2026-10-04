<?php

namespace App\Policies;

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Policies\Concerns\RequiresWorkspaceMembership;
use Illuminate\Auth\Access\Response;

class CardPolicy
{
    use RequiresWorkspaceMembership;

    /**
     * Determine whether the user can open the card.
     */
    public function view(User $user, Card $card): Response
    {
        return $this->memberOf($user, $card->boardList->board->workspace);
    }

    /**
     * Determine whether the user can add a card to the list.
     */
    public function create(User $user, BoardList $boardList): Response
    {
        return $this->memberOf($user, $boardList->board->workspace);
    }

    /**
     * Determine whether the user can update the card, including moving it to another list.
     */
    public function update(User $user, Card $card): Response
    {
        return $this->memberOf($user, $card->boardList->board->workspace);
    }

    /**
     * Determine whether the user can delete the card.
     */
    public function delete(User $user, Card $card): Response
    {
        return $this->memberOf($user, $card->boardList->board->workspace);
    }
}
