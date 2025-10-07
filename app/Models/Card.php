<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read BoardList $boardList
 */
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function boardList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class);
    }
}
