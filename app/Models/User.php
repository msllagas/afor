<?php

namespace App\Models;

 use Database\Factories\UserFactory;
 use Illuminate\Contracts\Auth\MustVerifyEmail;
 use Illuminate\Database\Eloquent\Concerns\HasUuids;
 use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Relations\BelongsToMany;
 use Illuminate\Database\Eloquent\Relations\HasMany;
 use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasUuids, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    // Workspaces this user is a member of
    public function sharedWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->as('sharedWorkspaces')
            ->withTimestamps();
    }
}
