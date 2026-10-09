<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id) {
    return $user->id === $id;
});

Broadcast::channel('workspace.{workspace}', function (User $user, Workspace $workspace) {
    return $workspace->isAccessibleBy($user);
});

Broadcast::channel('board.{board}', function (User $user, Board $board) {
    return $board->isAccessibleBy($user);
});
