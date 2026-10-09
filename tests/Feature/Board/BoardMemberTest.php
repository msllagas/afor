<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->member = User::factory()->create(['name' => 'Ada Lovelace']);
    $this->workspace->users()->attach($this->member);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->member)->create();
});

test('the owner adds a workspace member, who can then open the board', function () {
    $newcomer = User::factory()->create();
    $this->workspace->users()->attach($newcomer);

    $response = $this->actingAs($this->owner)
        ->from(route('boards.show', $this->board))
        ->post(route('boards.members.store', $this->board), ['user_id' => $newcomer->id]);

    $response->assertRedirect(route('boards.show', $this->board));
    $this->assertDatabaseHas('board_user', ['board_id' => $this->board->id, 'user_id' => $newcomer->id]);
    $this->actingAs($newcomer)->get(route('boards.show', $this->board))->assertOk();
});

test('adding someone to a board is rejected with a reason', function (Closure $userId, string $message) {
    $response = $this->actingAs($this->owner)
        ->postJson(route('boards.members.store', $this->board), ['user_id' => $userId->call($this)]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id' => $message]);
    $this->assertDatabaseCount('board_user', 1);
})->with([
    'nobody chosen'          => [fn () => null, 'Choose someone to add to the board.'],
    'outside the workspace'  => [fn () => User::factory()->create()->id, 'Only members of this workspace can be added to its boards.'],
    'the workspace owner'    => [fn () => $this->owner->id, 'The workspace owner is already on every board.'],
    'already on the board'   => [fn () => $this->member->id, 'This person is already on the board.'],
]);

test('board members cannot add people to the board', function () {
    $newcomer = User::factory()->create();
    $this->workspace->users()->attach($newcomer);

    $response = $this->actingAs($this->member)
        ->postJson(route('boards.members.store', $this->board), ['user_id' => $newcomer->id]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('board_user', ['user_id' => $newcomer->id]);
});

test('the owner removes a member, who then gets not found for the board and loses their star on it', function () {
    $this->member->favoriteBoards()->attach($this->board);

    $response = $this->actingAs($this->owner)
        ->from(route('boards.show', $this->board))
        ->delete(route('boards.members.destroy', [$this->board, $this->member]));

    $response->assertRedirect(route('boards.show', $this->board));
    $this->assertDatabaseMissing('board_user', ['board_id' => $this->board->id, 'user_id' => $this->member->id]);
    $this->assertDatabaseMissing('board_user_favorites', ['board_id' => $this->board->id, 'user_id' => $this->member->id]);
    $this->assertDatabaseHas('workspace_user', ['workspace_id' => $this->workspace->id, 'user_id' => $this->member->id]);
    $this->actingAs($this->member)->get(route('boards.show', $this->board))->assertNotFound();
});

test('a removed member who is added back can open the board again', function () {
    $this->actingAs($this->owner)->delete(route('boards.members.destroy', [$this->board, $this->member]));

    $this->actingAs($this->owner)->post(route('boards.members.store', $this->board), ['user_id' => $this->member->id]);

    $this->actingAs($this->member)->get(route('boards.show', $this->board))->assertOk();
});

test('board members cannot remove other members', function () {
    $otherMember = User::factory()->create();
    $this->workspace->users()->attach($otherMember);
    $this->board->members()->attach($otherMember);

    $response = $this->actingAs($this->member)
        ->deleteJson(route('boards.members.destroy', [$this->board, $otherMember]));

    $response->assertForbidden();
    $this->assertDatabaseHas('board_user', ['board_id' => $this->board->id, 'user_id' => $otherMember->id]);
});

test('the owner cannot be removed from a board because they are not one of its members', function () {
    $response = $this->actingAs($this->owner)
        ->deleteJson(route('boards.members.destroy', [$this->board, $this->owner]));

    $response->assertNotFound();
});

test('members leave a board, stay in the workspace and get not found for the board', function () {
    $this->member->favoriteBoards()->attach($this->board);

    $response = $this->actingAs($this->member)->delete(route('boards.leave', $this->board));

    $response->assertRedirect(route('workspaces.home', $this->workspace));
    $this->assertDatabaseMissing('board_user', ['board_id' => $this->board->id, 'user_id' => $this->member->id]);
    $this->assertDatabaseMissing('board_user_favorites', ['board_id' => $this->board->id, 'user_id' => $this->member->id]);
    $this->assertDatabaseHas('workspace_user', ['workspace_id' => $this->workspace->id, 'user_id' => $this->member->id]);
    $this->actingAs($this->member)->get(route('boards.show', $this->board))->assertNotFound();
});

test('the owner cannot leave a board in their workspace', function () {
    $response = $this->actingAs($this->owner)->deleteJson(route('boards.leave', $this->board));

    $response->assertForbidden()
        ->assertJsonPath('message', "You own this workspace, so you're on every board in it.");
});

test('board members see the owner and every member of the board but not who else could be added', function () {
    $otherMember = User::factory()->create(['name' => 'Grace Hopper']);
    $this->workspace->users()->attach($otherMember);
    $this->board->members()->attach($otherMember);

    $response = $this->actingAs($this->member)->get(route('boards.show', $this->board));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('owner.id', $this->owner->id)
        ->has('members', 2)
        ->where('members.0.id', $this->member->id)
        ->where('members.1.id', $otherMember->id)
        ->missing('owner.email')
        ->missing('members.0.email')
        ->where('canManageMembers', false)
        ->where('canLeave', true)
        ->missing('addableMembers')
        ->reload(fn (Assert $reload) => $reload->missing('addableMembers'), only: 'addableMembers')
    );
});

test('the owner sees the workspace members who are not on the board yet', function () {
    $newcomer = User::factory()->create();
    $this->workspace->users()->attach($newcomer);

    $response = $this->actingAs($this->owner)->get(route('boards.show', $this->board));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('canManageMembers', true)
        ->where('canLeave', false)
        ->has('addableMembers', 1)
        ->where('addableMembers.0.id', $newcomer->id)
        ->missing('addableMembers.0.email')
    );
});
