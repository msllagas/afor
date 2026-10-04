<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceInvitation>
 */
class WorkspaceInvitationFactory extends Factory
{
    /**
     * Define the model's default state: an invite link issued by the workspace owner.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'invited_by'   => fn (array $attributes) => Workspace::query()->findOrFail($attributes['workspace_id'])->owner_id,
            'token'        => fake()->unique()->regexify('[A-Za-z0-9]{32}'),
        ];
    }

    /**
     * An invite link issued by someone other than the owner, such as a member before invites became owner-only.
     */
    public function issuedBy(User $user): static
    {
        return $this->state([
            'invited_by' => $user->id,
        ]);
    }
}
