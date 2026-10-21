<?php

namespace App\Models;

use Database\Factories\BoardListFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
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
 * @property string|null $created_by
 * @property string|null $archived_by
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $archiver
 * @property-read User|null $creator
 * @property-read Board|null $board
 * @property-read Collection<int, Card> $cards
 * @property-read int|null $cards_count
 *
 * @method static Builder<static>|BoardList archived()
 * @method static BoardListFactory factory($count = null, $state = [])
 * @method static Builder<static>|BoardList newModelQuery()
 * @method static Builder<static>|BoardList newQuery()
 * @method static Builder<static>|BoardList onlyTrashed()
 * @method static Builder<static>|BoardList query()
 * @method static Builder<static>|BoardList unarchived()
 * @method static Builder<static>|BoardList whereArchivedAt($value)
 * @method static Builder<static>|BoardList whereArchivedBy($value)
 * @method static Builder<static>|BoardList whereBoardId($value)
 * @method static Builder<static>|BoardList whereColor($value)
 * @method static Builder<static>|BoardList whereCreatedAt($value)
 * @method static Builder<static>|BoardList whereDeletedAt($value)
 * @method static Builder<static>|BoardList whereId($value)
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
    use HasFactory, HasUuids, MassPrunable, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Who created it is kept for auditing and never sent to the browser.
     *
     * @var list<string>
     */
    protected $hidden = ['created_by'];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * Lists that were archived. Like boards, only archived_at decides: archived_by is cleared when the archiver deletes their account.
     */
    /**
     * Archived lists can be restored for a while, then they are removed for good with their cards.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNotNull('archived_at')
            ->where('archived_at', '<=', now()->subDays(config('limits.archive_retention_days')));
    }

    #[Scope]
    protected function archived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    #[Scope]
    protected function unarchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** @return BelongsTo<Board, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /** @return HasMany<Card, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('order');
    }
}
