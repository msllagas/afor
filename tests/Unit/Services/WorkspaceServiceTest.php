<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceService;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->user)->create();

    $this->service = app(WorkspaceService::class);
});

test('service generates invitation link for a workspace per user', function () {
    $link = $this->service->generateInvitationLink($this->workspace, $this->user);

    expect($link)
        ->toBeString()
        ->toContain("/invite/{$this->workspace->id}/");

    assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $this->workspace->id,
        'invited_by'   => $this->user->id,
        'token'        => Str::of($link)->afterLast('/'),
    ]);
});

test('service refuses to generate an invitation link for a workspace member', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member->id);

    expect(fn () => $this->service->generateInvitationLink($this->workspace, $member))
        ->toThrow(InvalidArgumentException::class, 'Only the workspace owner can invite people.');

    $this->assertDatabaseMissing('workspace_invitations', ['invited_by' => $member->id]);
});

test('service resets the invitation link by replacing every existing invitation', function () {
    $member = User::factory()->create();
    $ownerInvitation = WorkspaceInvitation::factory()->for($this->workspace)->create();
    $memberInvitation = WorkspaceInvitation::factory()->for($this->workspace)->issuedBy($member)->create();

    $link = $this->service->resetInvitationLink($this->workspace);

    expect(Str::of($link)->afterLast('/')->value())->not->toBe($ownerInvitation->token);
    $this->assertModelMissing($ownerInvitation);
    $this->assertModelMissing($memberInvitation);
    assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $this->workspace->id,
        'invited_by'   => $this->user->id,
        'token'        => Str::of($link)->afterLast('/'),
    ]);
});

test('service removes a removed member\'s stars on the workspace boards', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member->id);
    $board = Board::factory()->for($this->workspace)->create();
    $member->favoriteBoards()->attach($board->id);

    $this->service->removeMember($this->workspace, $member);

    $this->assertDatabaseMissing('board_user_favorites', ['user_id' => $member->id, 'board_id' => $board->id]);
});

test('service removes member from the workspace', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member->id);

    $this->service->removeMember($this->workspace, $member);

    $this->assertDatabaseMissing('workspace_user', [
        'workspace_id' => $this->workspace->id,
        'user_id'      => $member->id,
    ]);
});

test('service throws InvalidArgumentException when trying to remove the workspace owner', function () {
    $this->workspace->users()->attach($this->user->id); // attach the owner

    $this->service->removeMember($this->workspace, $this->user);
})->throws(InvalidArgumentException::class, 'Cannot remove the workspace owner.');
