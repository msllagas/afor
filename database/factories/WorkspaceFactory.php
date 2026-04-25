<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => 'Guest Workspace',
            'description' => fake()->text(),
            'owner_id'    => UserFactory::new(),
        ];
    }

    public function forUser(?User $user = null): static
    {
        $user ??= User::factory()->create();

        return $this->state([
            'name'     => $user->name.' Workspace',
            'owner_id' => $user->id,
        ]);
    }
}
