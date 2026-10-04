<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceService;
use Inertia\Testing\AssertableInertia as Assert;

test('guest users can view workspace invitation details', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();

    // Create a link for workspace invitation
    $service = app(WorkspaceService::class);
    $link = $service->generateInvitationLink($workspace, $user);
    $token = Str::of($link)->afterLast('/')->value();

    $response = $this->get(route('workspace-invitations.show', [
        'workspace' => $workspace,
        'token'     => $token,
    ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspace-invitations/Invite')
            ->has('invitation', fn (Assert $page) => $page
                ->where('token', $token)
                ->where('invited_by', $user->id)
                ->where('workspace_id', $workspace->id)
                ->has('inviter', fn (Assert $page) => $page
                    ->where('id', $user->id)
                    ->where('name', $user->name
                    ))
                ->has('workspace', fn (Assert $page) => $page
                    ->where('id', $workspace->id)
                    ->where('name', $workspace->name
                    ))
            )
        );
});

test('invite links a member created show not found', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member->id);
    $memberInvitation = WorkspaceInvitation::factory()->for($workspace)->issuedBy($member)->create();

    $response = $this->get(route('workspace-invitations.show', [
        'workspace' => $workspace,
        'token'     => $memberInvitation->token,
    ]));

    $response->assertNotFound();
});
