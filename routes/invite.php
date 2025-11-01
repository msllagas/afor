<?php

use App\Http\Controllers\WorkspaceInvitationController;

Route::get('/invite/{workspace}/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('workspace-invitations.show');
