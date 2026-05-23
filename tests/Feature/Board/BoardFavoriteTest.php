<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();
});

test('users can favorite a board from the workspace', function () {
    $board = Board::factory()->for($this->workspace)->create();

    $response = $this->actingAs($this->user)
        ->post(route('workspaces.boards.favorite', [
            'workspace' => $this->workspace,
            'board'     => $board,
        ]));

    $responseData = $response->json();

    $this->assertArrayHasKey('is_favorited', $responseData);
    $this->assertTrue($responseData['is_favorited']);

    $response->assertStatus(200);

    $this->assertDatabaseHas('board_user_favorites', [
        'user_id'  => $this->user->id,
        'board_id' => $board->id,
    ]);

    $favorite = DB::table('board_user_favorites')
        ->where('user_id', $this->user->id)
        ->where('board_id', $board->id)
        ->first();

    expect($favorite->created_at)->not->toBeNull();
});

test('users can unfavorite a board from the workspace', function () {
    $board = Board::factory()->for($this->workspace)->create();
    $this->user->favoriteBoards()->attach($board);

    $this->assertDatabaseHas('board_user_favorites', [
        'user_id'  => $this->user->id,
        'board_id' => $board->id,
    ]);

    $this->actingAs($this->user)
        ->post(route('workspaces.boards.favorite', [
            'workspace' => $this->workspace,
            'board'     => $board,
        ]));

    $this->assertDatabaseMissing('board_user_favorites', [
        'user_id'  => $this->user->id,
        'board_id' => $board->id,
    ]);
});
