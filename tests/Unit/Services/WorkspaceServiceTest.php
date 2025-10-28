<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;

test('service generates invitation link for a workspace per user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspace = Workspace::factory()->for($user)->create();

    $service = app(WorkspaceService::class);

    $link = $service->generateInvitationLink($workspace, $user);

    expect($link)
        ->toBeString()
        ->toContain("/invite/{$workspace->id}/");
});
