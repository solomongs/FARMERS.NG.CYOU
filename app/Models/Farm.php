<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'type', 'email', 'phone',
        'registration_number', 'country', 'state', 'address',
        'description', 'currency', 'timezone', 'capacity',
        'logo_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'farm_user')
            ->withPivot(['id', 'role_id', 'status', 'joined_at', 'last_active_at'])
            ->withTimestamps();
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->withPivot('enabled')
            ->withTimestamps();
    }
}
