<?php

use App\Models\User;
use App\Models\Workspace;
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

test('service throws InvalidArgumentException when trying to remove a non-member', function () {
    $nonMember = User::factory()->create();

    $this->service->removeMember($this->workspace, $nonMember);
})->throws(InvalidArgumentException::class, 'User is not a member of this workspace.');
