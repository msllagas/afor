<?php

namespace App\Http\Resources;

use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Board */
class BoardResource extends JsonResource
{
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
            'workspace_id' => $this->workspace_id,
            'created_at'   => $this->created_at,
            'archived_at'  => $this->whenHas('archived_at'),
            'is_favorited' => $this->whenHas('is_favorited'),
            'archiver'     => $this->whenLoaded('archiver', fn () => $this->archiver?->only('id', 'name')),
            'board_lists'  => $this->whenLoaded('boardLists'),
        ];
    }
}
