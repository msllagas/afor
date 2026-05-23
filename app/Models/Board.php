<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $workspace_id
 * @property string|null $archived_by
 * @property int|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, BoardList> $boardLists
 * @property-read int|null $board_lists_count
 * @property-read Collection<int, User> $favoritedByUsers
 * @property-read int|null $favorited_by_users_count
 * @property-read Workspace $workspace
 *
 * @method static Builder<static>|Board archived()
 * @method static BoardFactory factory($count = null, $state = [])
 * @method static Builder<static>|Board newModelQuery()
 * @method static Builder<static>|Board newQuery()
 * @method static Builder<static>|Board onlyTrashed()
 * @method static Builder<static>|Board query()
 * @method static Builder<static>|Board unarchived()
 * @method static Builder<static>|Board whereArchivedAt($value)
 * @method static Builder<static>|Board whereArchivedBy($value)
 * @method static Builder<static>|Board whereCreatedAt($value)
 * @method static Builder<static>|Board whereDeletedAt($value)
 * @method static Builder<static>|Board whereId($value)
 * @method static Builder<static>|Board whereName($value)
 * @method static Builder<static>|Board whereUpdatedAt($value)
 * @method static Builder<static>|Board whereWorkspaceId($value)
 * @method static Builder<static>|Board withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Board withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'archived_at' => 'timestamp',
    ];

    #[Scope]
    public function archived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at')
            ->whereNotNull('archived_by');
    }

    #[Scope]
    public function unarchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
            ->whereNull('archived_by');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function boardLists(): HasMany
    {
        return $this->hasMany(BoardList::class)
            ->orderBy('order');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_user_favorites');
    }
}
