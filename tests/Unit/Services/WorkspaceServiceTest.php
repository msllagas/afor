<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use function Pest\Laravel\assertDatabaseHas;

test('service generates invitation link for a workspace per user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->for($user)->create();

    $service = app(WorkspaceService::class);

    $link = $service->generateInvitationLink($workspace, $user);

    expect($link)
        ->toBeString()
        ->toContain("/invite/{$workspace->id}/");

    assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $workspace->id,
        'invited_by' => $user->id,
        'token' => Str::of($link)->afterLast('/'),
    ]);
});
