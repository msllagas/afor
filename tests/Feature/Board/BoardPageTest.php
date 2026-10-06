<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->for(Workspace::factory()->forUser($this->user))->create();
    $this->boardList = BoardList::factory()->for($this->board)->create();
});

test('the board flags which cards have a description without sending it', function () {
    $describedCard = Card::factory()->for($this->boardList)->create(['order' => 0, 'description' => '<p>Pack the tent</p>']);
    Card::factory()->for($this->boardList)->create(['order' => 1, 'description' => null]);

    $response = $this->actingAs($this->user)->get(route('boards.show', $this->board));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Show')
            ->has('board.board_lists.0.cards', 2)
            ->has('board.board_lists.0.cards.0', fn (Assert $card) => $card
                ->where('id', $describedCard->id)
                ->where('name', $describedCard->name)
                ->where('order', 0)
                ->where('board_list_id', $this->boardList->id)
                ->where('has_description', true)
            )
            ->where('board.board_lists.0.cards.1.has_description', false)
        );
});

test('an open card brings its description, also when it is reloaded on its own', function () {
    $card = Card::factory()->for($this->boardList)->create(['description' => '<p>Pack the tent</p>']);

    $response = $this->actingAs($this->user)->get(route('board-lists.cards.show', [$this->boardList, $card]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedCard.id', $card->id)
            ->where('selectedCard.description', '<p>Pack the tent</p>')
            ->reloadOnly('selectedCard', fn (Assert $reload) => $reload
                ->where('selectedCard.description', '<p>Pack the tent</p>')
                ->missing('board')
            )
        );
});
