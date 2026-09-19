<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    protected $table = 'farm_user';

    protected $fillable = [
        'farm_id', 'user_id', 'role_id', 'status', 'joined_at', 'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
