<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin User
 *
 * @property-read Pivot&object{created_at: Carbon|null} $pivot the workspace membership, present when the user was loaded through a workspace's members
 */
class WorkspaceMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'avatar'    => $this->whenLoaded('avatarFile', fn () => $this->avatar),
            'joined_at' => $this->whenPivotLoaded('workspace_user', fn () => $this->pivot->created_at?->toIso8601String()),
        ];
    }
}
