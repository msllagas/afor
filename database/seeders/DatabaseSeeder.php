<?php

namespace Database\Seeders;

use App\Enums\BoardListColor;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $mandy = User::factory()->withoutTwoFactor()->create([
            'name' => 'Mandy The Creator',
            'email' => 'mandy.afor@example.com',
        ]);

        $angel = User::factory()->withoutTwoFactor()->create([
            'name' => 'Angel The Girlfriend',
            'email' => 'angel.afor@example.com',
        ]);

        $mandyWorkspace = Workspace::factory()->forUser($mandy)->create();
        $angelWorkspace = Workspace::factory()->forUser($angel)->create();

        foreach ([$mandyWorkspace, $angelWorkspace] as $workspace) {
            $board = Board::factory()->for($workspace)->create(['name' => 'Hello Afor']);

            BoardList::factory()->for($board)->createMany([
                ['name' => 'To Do', 'order' => 0, 'color' => BoardListColor::BLUE->value],
                ['name' => 'In Progress', 'order' => 1, 'color' => BoardListColor::AMBER->value],
                ['name' => 'Done', 'order' => 2, 'color' => BoardListColor::GREEN->value],
            ]);

            Card::factory()->for($board->boardLists->first())->createMany([
                ['name' => 'Set up project structure'],
                ['name' => 'Review requirements'],
                ['name' => 'Create initial documentation'],
            ]);
        }
    }
}
