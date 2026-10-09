<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
