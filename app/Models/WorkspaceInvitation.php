<?php

namespace App\Models;

use Database\Factories\WorkspaceInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @method static Builder<static>|WorkspaceInvitation validFor(Workspace $workspace, string $token)
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
    /** @use HasFactory<WorkspaceInvitationFactory> */
    use HasFactory, HasUuids;

    protected $table = 'workspace_invitations';

    protected $guarded = ['id'];

    /**
     * Invitations that still let people join: the token belongs to the workspace and was issued by its owner.
     *
     * A link a member created before invites became owner-only, or one issued by a previous owner, no longer works.
     */
    #[Scope]
    public function validFor(Builder $query, Workspace $workspace, string $token): Builder
    {
        return $query->whereBelongsTo($workspace)
            ->where('token', $token)
            ->where('invited_by', $workspace->owner_id);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
