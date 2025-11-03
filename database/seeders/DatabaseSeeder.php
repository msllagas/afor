<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $mandy = User::factory()->withoutTwoFactor()->create([
            'name' => 'Mandy The Creator',
            'email' => 'mandy.afor@example.com',
        ]);

        $angel = User::factory()->withoutTwoFactor()->create([
            'name' => 'Angel The Girlfriend',
            'email' => 'angel.afor@example.com',
        ]);

        Workspace::factory()->forUser($mandy)->create();
        Workspace::factory()->forUser($angel)->create();
    }
}
