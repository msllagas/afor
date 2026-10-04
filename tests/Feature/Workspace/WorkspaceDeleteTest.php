<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
});

test('workspace owner can delete their workspace with its boards, members and logo', function () {
    Storage::fake('public');
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);
    $board = Board::factory()->for($this->workspace)->create();

    $this->actingAs($this->owner)
        ->patchJson(route('workspaces.update', $this->workspace), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])
        ->assertStatus(302);
    $logo = $this->workspace->logoFile()->first();

    $this->actingAs($this->owner)
        ->delete(route('workspaces.destroy', $this->workspace))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('workspaces', ['id' => $this->workspace->id]);
    $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    $this->assertDatabaseMissing('workspace_user', ['workspace_id' => $this->workspace->id]);
    $this->assertDatabaseEmpty('files');
    Storage::disk('public')->assertMissing($logo->path);
    $this->assertDatabaseHas('users', ['id' => $member->id]);
});

test('workspace members cannot delete the workspace', function () {
    $member = User::factory()->create();
    $this->workspace->users()->attach($member);

    $this->actingAs($member)
        ->delete(route('workspaces.destroy', $this->workspace))
        ->assertForbidden();

    $this->assertDatabaseHas('workspaces', ['id' => $this->workspace->id]);
});

test('users outside the workspace cannot delete it', function () {
    $this->actingAs(User::factory()->create())
        ->delete(route('workspaces.destroy', $this->workspace))
        ->assertNotFound();

    $this->assertDatabaseHas('workspaces', ['id' => $this->workspace->id]);
});

test('guests cannot delete a workspace', function () {
    $this->delete(route('workspaces.destroy', $this->workspace))
        ->assertRedirect(route('login'));

    $this->assertDatabaseHas('workspaces', ['id' => $this->workspace->id]);
});
