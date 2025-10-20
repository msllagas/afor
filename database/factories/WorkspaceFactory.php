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
            'name' => 'Guest Workspace',
            'user_id' => UserFactory::new(),
        ];
    }

    public function forUser(User $user = null): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $user?->name . "'s Workspace" ?? 'Guest Workspace',
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
