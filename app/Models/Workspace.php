<?php

namespace App\Models;

use App\Enums\FileCollection;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Board> $boards
 * @property-read int|null $boards_count
 * @property-read mixed $logo
 * @property-read File|null $logoFile
 * @property-read User $owner
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static WorkspaceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Workspace newModelQuery()
 * @method static Builder<static>|Workspace newQuery()
 * @method static Builder<static>|Workspace query()
 * @method static Builder<static>|Workspace whereCreatedAt($value)
 * @method static Builder<static>|Workspace whereDescription($value)
 * @method static Builder<static>|Workspace whereId($value)
 * @method static Builder<static>|Workspace whereName($value)
 * @method static Builder<static>|Workspace whereOwnerId($value)
 * @method static Builder<static>|Workspace whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->owner_id === $user->id
            || $this->users()->whereKey($user->id)->exists();
    }

    #[Scope]
    protected function accessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('owner_id', $user->id)
            ->orWhereHas('users', fn (Builder $query) => $query->whereKey($user->id)));
    }

    // Members of the workspace
    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withTimestamps();
    }

    /** @return HasMany<Board, $this> */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /** @return MorphOne<File, $this> */
    public function logoFile(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable')
            ->where('collection', FileCollection::WORKSPACE_LOGO->value);
    }

    public function logo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logoFile
                ? Storage::url($this->logoFile->path)
                : null
        );
    }
}
