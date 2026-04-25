<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('workspace.{workspaceId}', function (User $user, $workspaceId) {
    return !is_null($user->workspaces->firstWhere('id', $workspaceId));
});
