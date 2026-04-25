<?php

namespace Database\Factories;

use App\Models\BoardList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardList>
 */
class BoardListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => fake()->name(),
            'board_id'    => BoardFactory::new(),
            'order'       => 0,
            'is_archived' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }
}
