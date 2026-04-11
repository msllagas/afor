<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $fileable_type
 * @property string $fileable_id
 * @property string $collection
 * @property string $disk
 * @property string $path
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int|null $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $fileable
 * @property-read User|null $uploader
 *
 * @method static Builder<static>|File newModelQuery()
 * @method static Builder<static>|File newQuery()
 * @method static Builder<static>|File query()
 * @method static Builder<static>|File whereCollection($value)
 * @method static Builder<static>|File whereCreatedAt($value)
 * @method static Builder<static>|File whereDisk($value)
 * @method static Builder<static>|File whereFileableId($value)
 * @method static Builder<static>|File whereFileableType($value)
 * @method static Builder<static>|File whereId($value)
 * @method static Builder<static>|File whereMimeType($value)
 * @method static Builder<static>|File whereOriginalFilename($value)
 * @method static Builder<static>|File wherePath($value)
 * @method static Builder<static>|File whereSize($value)
 * @method static Builder<static>|File whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class File extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
