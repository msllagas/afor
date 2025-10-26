<?php
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace owner can access their workspace members', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Member')
        );
});

test('workspace members can access their workspace members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->for($owner)->create();
    $workspace->users()->attach($member->id);

    $response = $this->actingAs($member)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Member')
        );
});

test('user that is not a member or owner of the workspace are redirected to dashboard', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $workspace = Workspace::factory()->for($anotherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('workspaces.members', [
            'workspace' => $workspace,
        ]));

    $response->assertRedirect(route('dashboard'));
});

