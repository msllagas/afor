<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

test('workspace members can leave the workspace', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->delete(route('workspaces.leave', ['workspace' => $workspace]));

    $response->assertRedirect(route('boards.index'));
    $this->assertDatabaseMissing('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id'      => $member->id,
    ]);
});

test('leaving a workspace removes only the stars the member gave its boards', function () {
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach([$member->id, $otherMember->id]);
    $leftBoard = Board::factory()->for($workspace)->create();
    $keptBoard = Board::factory()->for(Workspace::factory()->forUser($member))->create();
    $member->favoriteBoards()->attach([$leftBoard->id, $keptBoard->id]);
    $otherMember->favoriteBoards()->attach($leftBoard->id);

    $this->actingAs($member)->delete(route('workspaces.leave', ['workspace' => $workspace]));

    $this->assertDatabaseMissing('board_user_favorites', ['user_id' => $member->id, 'board_id' => $leftBoard->id]);
    $this->assertDatabaseHas('board_user_favorites', ['user_id' => $member->id, 'board_id' => $keptBoard->id]);
    $this->assertDatabaseHas('board_user_favorites', ['user_id' => $otherMember->id, 'board_id' => $leftBoard->id]);
});

test('leaving the last opened workspace forgets it', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member->id);
    $member->forceFill(['last_workspace_id' => $workspace->id])->save();

    $this->actingAs($member)->delete(route('workspaces.leave', ['workspace' => $workspace]));

    expect($member->refresh()->last_workspace_id)->toBeNull();
});

test('workspace owners cannot leave their own workspace', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();

    $response = $this->actingAs($owner)
        ->delete(route('workspaces.leave', ['workspace' => $workspace]));

    $response->assertForbidden();
    expect($workspace->refresh()->owner_id)->toBe($owner->id);
});

test('users outside the workspace get not found when leaving it', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $response = $this->actingAs(User::factory()->create())
        ->delete(route('workspaces.leave', ['workspace' => $workspace]));

    $response->assertNotFound();
});

test('guests are sent to log in when leaving a workspace', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $response = $this->delete(route('workspaces.leave', ['workspace' => $workspace]));

    $response->assertRedirect(route('login'));
});
