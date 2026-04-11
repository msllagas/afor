<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $token
 * @property string $workspace_id
 * @property string $invited_by User who invited the workspace
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $inviter
 * @property-read Workspace $workspace
 *
 * @method static Builder<static>|WorkspaceInvitation newModelQuery()
 * @method static Builder<static>|WorkspaceInvitation newQuery()
 * @method static Builder<static>|WorkspaceInvitation query()
 * @method static Builder<static>|WorkspaceInvitation whereCreatedAt($value)
 * @method static Builder<static>|WorkspaceInvitation whereId($value)
 * @method static Builder<static>|WorkspaceInvitation whereInvitedBy($value)
 * @method static Builder<static>|WorkspaceInvitation whereToken($value)
 * @method static Builder<static>|WorkspaceInvitation whereUpdatedAt($value)
 * @method static Builder<static>|WorkspaceInvitation whereWorkspaceId($value)
 *
 * @mixin \Eloquent
 */
class WorkspaceInvitation extends Model
{
    use HasUuids;

    protected $table = 'workspace_invitations';

    protected $guarded = ['id'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
