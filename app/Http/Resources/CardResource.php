<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'board_list_id' => $this->board_list_id,
            'deleted_at'    => $this->whenHas('deleted_at'),
            'board_list'    => new BoardListResource($this->whenLoaded('boardList')),
        ];
    }
}
