<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read BoardList $boardList
 */
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasUuids, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function boardList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class);
    }
}
