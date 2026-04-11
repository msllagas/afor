<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property int $order
 * @property string $board_list_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read BoardList|null $boardList
 *
 * @method static CardFactory factory($count = null, $state = [])
 * @method static Builder<static>|Card newModelQuery()
 * @method static Builder<static>|Card newQuery()
 * @method static Builder<static>|Card onlyTrashed()
 * @method static Builder<static>|Card query()
 * @method static Builder<static>|Card whereBoardListId($value)
 * @method static Builder<static>|Card whereCreatedAt($value)
 * @method static Builder<static>|Card whereDeletedAt($value)
 * @method static Builder<static>|Card whereDescription($value)
 * @method static Builder<static>|Card whereId($value)
 * @method static Builder<static>|Card whereName($value)
 * @method static Builder<static>|Card whereOrder($value)
 * @method static Builder<static>|Card whereUpdatedAt($value)
 * @method static Builder<static>|Card withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Card withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    public function boardList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class);
    }
}
