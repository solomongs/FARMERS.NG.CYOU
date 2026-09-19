<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country',
        'state',
        'password',
        'is_superuser',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_superuser' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function farms()
    {
        return $this->belongsToMany(Farm::class, 'farm_user')
            ->withPivot(['id', 'role_id', 'status', 'joined_at', 'last_active_at'])
            ->withTimestamps();
    }

    public function ownedFarms()
    {
        return $this->hasMany(Farm::class, 'owner_id');
    }

    public function membershipFor(int $farmId): ?Membership
    {
        return Membership::query()
            ->where('farm_id', $farmId)
            ->where('user_id', $this->id)
            ->where('status', 'active')
            ->first();
    }

    public function hasFarmPermission(int $farmId, string $permission): bool
    {
        if ($this->is_superuser) {
            return true;
        }

        $membership = $this->membershipFor($farmId);

        if (!$membership) {
            return false;
        }

        $override = UserPermission::query()
            ->where('farm_id', $farmId)
            ->where('user_id', $this->id)
            ->whereHas('permission', fn ($query) => $query->where('key', $permission))
            ->first();

        if ($override) {
            return (bool) $override->allowed;
        }

        return $membership->role?->permissions()->where('key', $permission)->exists() ?? false;
    }
}
