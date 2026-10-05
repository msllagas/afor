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
 * @property-read Collection<int, User> $members
 * @property-read int|null $members_count
 * @property-read Workspace $workspace
 * @property-read User|null $archiver
 *
 * @method static Builder<static>|Board archived()
 * @method static BoardFactory factory($count = null, $state = [])
 * @method static Builder<static>|Board newModelQuery()
 * @method static Builder<static>|Board newQuery()
 * @method static Builder<static>|Board onlyTrashed()
 * @method static Builder<static>|Board query()
 * @method static Builder<static>|Board unarchived()
 * @method static Builder<static>|Board visibleTo(User $user)
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

    /**
     * Boards the user can see: every board in a workspace they own, plus the boards they were added to
     * in workspaces they're still a member of. Keep in step with isAccessibleBy().
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereHas('workspace', fn (Builder $query) => $query->where('owner_id', $user->id))
            ->orWhere(fn (Builder $query) => $query
                ->whereHas('members', fn (Builder $query) => $query->whereKey($user->id))
                ->whereHas('workspace.users', fn (Builder $query) => $query->whereKey($user->id))));
    }

    /**
     * Whether the user can see and work on the board. The workspace owner always can; anyone else needs
     * to be on the board and still in its workspace, so a membership left behind never grants access.
     * Keep in step with the visibleTo() scope.
     */
    public function isAccessibleBy(User $user): bool
    {
        return $this->isOwnedBy($user)
            || $this->members()
                ->whereKey($user->id)
                ->whereHas('sharedWorkspaces', fn (Builder $query) => $query->whereKey($this->workspace_id))
                ->exists();
    }

    /**
     * Whether the user owns the board's workspace, which gives them every board in it.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->workspace->owner_id === $user->id;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function boardLists(): HasMany
    {
        return $this->hasMany(BoardList::class)
            ->orderBy('order');
    }

    /**
     * Workspace members who were added to the board. The workspace owner is never one of them.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_user_favorites');
    }
}
