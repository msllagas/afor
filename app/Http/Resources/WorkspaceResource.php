<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description ?? null,
            'boards'       => $this->whenLoaded('boards', fn () => $this->boards, []),
            'logo'         => $this->whenLoaded('logoFile', fn () => $this->logo, []),
        ];
    }
}
