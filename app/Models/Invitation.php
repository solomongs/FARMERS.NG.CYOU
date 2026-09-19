<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'farm_id', 'role_id', 'invited_by', 'name', 'email',
        'token_hash', 'expires_at', 'accepted_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isUsable(): bool
    {
        return !$this->accepted_at && !$this->cancelled_at && $this->expires_at?->isFuture();
    }
}
