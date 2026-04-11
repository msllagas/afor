<?php

namespace App\Models;

use Database\Factories\BoardListFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property int $order
 * @property string|null $color
 * @property string $board_id
 * @property int $is_archived
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Board|null $board
 * @property-read Collection<int, Card> $cards
 * @property-read int|null $cards_count
 *
 * @method static Builder<static>|BoardList active()
 * @method static BoardListFactory factory($count = null, $state = [])
 * @method static Builder<static>|BoardList newModelQuery()
 * @method static Builder<static>|BoardList newQuery()
 * @method static Builder<static>|BoardList onlyTrashed()
 * @method static Builder<static>|BoardList query()
 * @method static Builder<static>|BoardList whereBoardId($value)
 * @method static Builder<static>|BoardList whereColor($value)
 * @method static Builder<static>|BoardList whereCreatedAt($value)
 * @method static Builder<static>|BoardList whereDeletedAt($value)
 * @method static Builder<static>|BoardList whereId($value)
 * @method static Builder<static>|BoardList whereIsArchived($value)
 * @method static Builder<static>|BoardList whereName($value)
 * @method static Builder<static>|BoardList whereOrder($value)
 * @method static Builder<static>|BoardList whereUpdatedAt($value)
 * @method static Builder<static>|BoardList withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|BoardList withoutTrashed()
 *
 * @mixin \Eloquent
 */
class BoardList extends Model
{
    /** @use HasFactory<BoardListFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    #[Scope]
    public function active(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('order');
    }
}
