<?php

namespace Database\Seeders;

use App\Models\BoardList;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class BoardListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Model::unguard();
       $this->create();
       Model::reguard();
    }

    private function create()
    {
        $boardId = $this->command->argument('board');

        $presetBoards = [
            'To Do',
            'In Progress',
            'Done',
        ];
        foreach ($presetBoards as $boardName) {
            BoardList::query()->create([
                'name' => $boardName,
                'board_id' => $boardId,
            ]);
        }
    }
}
