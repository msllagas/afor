<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create(['name' => 'Mandy Owner', 'email' => 'mandy@example.com']);
    $this->member = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $this->workspace = Workspace::factory()->forUser($this->owner)->create(['name' => 'Camping']);
    $this->workspace->users()->attach($this->member);
});

it('lists the people of a workspace without their email addresses', function () {
    $this->actingAs($this->member);

    visit(route('workspaces.members', $this->workspace))
        ->assertSee('Mandy Owner')
        ->assertSee('Owner')
        ->assertSee('Ada Lovelace')
        ->assertDontSee('mandy@example.com')
        ->assertDontSee('ada@example.com')
        ->assertDontSee('Created this workspace')
        ->assertNoJavaScriptErrors();
});

it('lets a member leave the workspace from the top of its members page', function () {
    $this->actingAs($this->member);

    visit(route('workspaces.members', $this->workspace))
        ->click('#leave-workspace')
        ->assertSee('Leave Camping?')
        ->click('[role="group"] button:has-text("Leave workspace")')
        ->assertSee('You left Camping')
        ->assertNoJavaScriptErrors();

    expect($this->workspace->users()->whereKey($this->member->id)->exists())->toBeFalse();
});

it('does not offer the owner to leave their own workspace', function () {
    $this->actingAs($this->owner);

    visit(route('workspaces.members', $this->workspace))
        ->assertNotPresent('#leave-workspace')
        ->assertNoJavaScriptErrors();
});
