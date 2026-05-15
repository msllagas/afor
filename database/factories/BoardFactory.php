<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => $this->faker->name(),
            'workspace_id' => WorkspaceFactory::new(),
            'archived_at'  => null,
            'archived_by'  => null,
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

    public function unarchived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => null,
            'archived_by' => null,
        ]);
    }
}
