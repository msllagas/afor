<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

test('changes are throttled per user while reads are not', function () {
    config(['limits.writes_per_minute' => 2]);
    $user = User::factory()->create();
    $board = Board::factory()->for(Workspace::factory()->forUser($user))->create();

    $this->actingAs($user);
    $this->patch(route('boards.update', $board), ['name' => 'First'])->assertRedirect();
    $this->patch(route('boards.update', $board), ['name' => 'Second'])->assertRedirect();
    $this->get(route('boards.show', $board))->assertOk();

    $this->patch(route('boards.update', $board), ['name' => 'Third'])->assertTooManyRequests();
    expect($board->refresh()->name)->toBe('Second');
});
