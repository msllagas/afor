<?php

namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{

    protected static int $order = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Card::factory(4)->create();
    }
}
