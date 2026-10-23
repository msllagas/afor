<?php

namespace App\Models;

use App\Services\CardDescriptionSanitizer;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_checked
 * @property int $order
 * @property string $board_list_id
 * @property string|null $created_by
 * @property string|null $archived_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read BoardList|null $boardList
 * @property-read User|null $creator
 * @property-read User|null $archiver
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
    use HasFactory, HasUuids, MassPrunable, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Who created it is kept for auditing and never sent to the browser.
     *
     * @var list<string>
     */
    protected $hidden = ['created_by'];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    /**
     * Archived cards can be restored for a while, then they are removed for good.
     * Pruning adds withTrashed() itself, so trashed cards are matched without onlyTrashed().
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('deleted_at', '<=', now()->subDays(config('limits.archive_retention_days')));
    }

    /** @return BelongsTo<BoardList, $this> */
    public function boardList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class);
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

    /**
     * A description is cut down to the markup the editor produces before it is stored, so it is safe wherever
     * it is shown later. One with no text left, such as the editor's empty paragraph, is stored as null,
     * so whether a card has a description can be read without loading it.
     */
    public function description(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                $description = app(CardDescriptionSanitizer::class)->sanitize((string) $value);

                return blank(strip_tags($description)) ? null : $description;
            },
        );
    }
}
