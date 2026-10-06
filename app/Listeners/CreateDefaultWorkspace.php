<?php

namespace App\Listeners;

use App\Enums\BoardListColor;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class CreateDefaultWorkspace
{
    /**
     * Give the new user a workspace with a starter board. It is created in full or not at all.
     */
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        DB::transaction(function () use ($user) {
            $workspace = $user->ownedWorkspaces()->create([
                'name' => $user->name,
            ]);

            $board = $workspace->boards()->create([
                'name' => 'Getting Started',
            ]);

            $todo = $board->boardLists()->create([
                'name'  => 'To Do',
                'order' => 0,
                'color' => BoardListColor::BLUE->value,
            ]);

            $inProgress = $board->boardLists()->create([
                'name'  => 'In Progress',
                'order' => 1,
                'color' => BoardListColor::YELLOW->value,
            ]);

            $done = $board->boardLists()->create([
                'name'  => 'Done',
                'order' => 2,
                'color' => BoardListColor::GREEN->value,
            ]);

            $todo->cards()->createMany([
                ['name' => 'Welcome to your board 🎉'],
                ['name' => 'Create your first task'],
            ]);

            $inProgress->cards()->create([
                'name' => 'Drag cards between lists',
            ]);

            $done->cards()->create([
                'name' => 'You completed onboarding!',
            ]);
        });
    }
}
