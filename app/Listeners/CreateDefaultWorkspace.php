<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class CreateDefaultWorkspace
{

    /**
     * @param Registered $event
     * @return void
     */
    public function handle(Registered $event): void
    {
        $event->user?->ownedWorkspaces()->create([
            'name' => $event->user->name . ' Workspace'
        ]);
    }
}
