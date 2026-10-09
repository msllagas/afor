<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Broadcast;

beforeEach(function () {
    config([
        'broadcasting.default'                   => 'reverb',
        'broadcasting.connections.reverb.key'    => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);
    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');

    $this->workspace = Workspace::factory()->forUser()->create();
    $this->owner = $this->workspace->owner;
    $this->boardMember = User::factory()->create();
    $this->workspaceMember = User::factory()->create();
    $this->workspace->users()->attach([$this->boardMember->id, $this->workspaceMember->id]);
    $this->board = Board::factory()->for($this->workspace)->withMembers($this->boardMember)->create();
    $this->outsider = User::factory()->create();
});

function subscribeTo(string $channel, ?User $user)
{
    $user && test()->actingAs($user);

    return test()->postJson('/broadcasting/auth', [
        'socket_id'    => '1234.5678',
        'channel_name' => "private-{$channel}",
    ]);
}

test('people with access to a board can listen to it', function (string $actor) {
    subscribeTo("board.{$this->board->id}", $this->{$actor})
        ->assertOk()
        ->assertJsonStructure(['auth']);
})->with([
    'the workspace owner' => 'owner',
    'a board member'      => 'boardMember',
]);

test('an archived board can still be listened to by the people who can open it', function () {
    $this->board->update(['archived_at' => now()]);

    subscribeTo("board.{$this->board->id}", $this->boardMember)->assertOk();
});

test('everyone else is refused the board channel', function (string $actor) {
    subscribeTo("board.{$this->board->id}", $this->{$actor})->assertForbidden();
})->with([
    'a workspace member who is not on the board' => 'workspaceMember',
    'someone from another workspace'             => 'outsider',
]);

test('a board member who left the workspace is refused the board channel', function () {
    $this->workspace->users()->detach($this->boardMember->id);

    $this->assertDatabaseHas('board_user', ['board_id' => $this->board->id, 'user_id' => $this->boardMember->id]);
    subscribeTo("board.{$this->board->id}", $this->boardMember->refresh())->assertForbidden();
});

test('a deleted board has no channel to listen to', function () {
    $this->board->delete();

    subscribeTo("board.{$this->board->id}", $this->owner)->assertForbidden();
});

test('a board that does not exist has no channel to listen to', function () {
    subscribeTo('board.'.fake()->uuid(), $this->owner)->assertForbidden();
});

test('a guest is refused every channel', function (string $channel) {
    subscribeTo(str_replace(['{board}', '{workspace}', '{user}'], [$this->board->id, $this->workspace->id, $this->owner->id], $channel), null)
        ->assertForbidden();
})->with([
    'a board'     => 'board.{board}',
    'a workspace' => 'workspace.{workspace}',
    'a user'      => 'App.Models.User.{user}',
]);

test('the people in a workspace can listen to it', function (string $actor) {
    subscribeTo("workspace.{$this->workspace->id}", $this->{$actor})->assertOk();
})->with([
    'the owner'  => 'owner',
    'a member'   => 'workspaceMember',
]);

test('someone outside a workspace is refused its channel', function () {
    subscribeTo("workspace.{$this->workspace->id}", $this->outsider)->assertForbidden();
});

test('a workspace channel for a workspace that does not exist is refused', function () {
    subscribeTo('workspace.'.fake()->uuid(), $this->owner)->assertForbidden();
});

test('people can listen to their own user channel', function () {
    subscribeTo("App.Models.User.{$this->owner->id}", $this->owner)->assertOk();
});

test('nobody can listen to the user channel of someone else', function (string $actor) {
    subscribeTo("App.Models.User.{$this->owner->id}", $this->{$actor})->assertForbidden();
})->with([
    'a workspace member' => 'workspaceMember',
    'an outsider'        => 'outsider',
]);

test('a user id that only starts like another is not the same user', function () {
    $lastCharacter = substr($this->owner->id, -1);
    $lookalike = User::factory()->create([
        'id' => substr($this->owner->id, 0, -1).($lastCharacter === 'a' ? 'b' : 'a'),
    ]);

    subscribeTo("App.Models.User.{$this->owner->id}", $lookalike)->assertForbidden();
});
