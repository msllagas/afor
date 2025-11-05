<?php

use App\Http\Controllers\WorkspaceInvitationController;

Route::get('/invite/{workspace}/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('workspace-invitations.show');

Route::post('/invite/{workspace}/{token}/accept', [WorkspaceInvitationController::class, 'accept'])
    ->name('workspace-invitations.accept')
    ->middleware(['auth', 'verified']);
