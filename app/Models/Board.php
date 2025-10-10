<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Collection<int, BoardList> $boardLists
 */
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasUuids, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function boardLists(): HasMany
    {
        return $this->hasMany(BoardList::class);
    }
}
