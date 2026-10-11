<?php

namespace App\Http\Resources;

use App\Models\BoardList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BoardList */
class BoardListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'color'       => $this->whenHas('color'),
            'board_id'    => $this->whenHas('board_id'),
            'archived_at' => $this->whenHas('archived_at'),
            'archiver'    => $this->whenLoaded('archiver', fn () => $this->archiver?->only('id', 'name')),
            'cards_count' => $this->whenCounted('cards'),
        ];
    }
}
