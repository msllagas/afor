<?php

namespace Database\Factories;

use App\Models\BoardList;
use App\Models\User;
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
            'name'       => fake()->name(),
            'board_id'   => BoardFactory::new(),
            'order'      => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function archived(?User $archiver = null): static
    {
        $archiver ??= User::factory()->create();

        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
            'archived_by' => $archiver->id,
        ]);
    }
}
