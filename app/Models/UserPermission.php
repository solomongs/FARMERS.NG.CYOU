<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $fillable = ['farm_id', 'user_id', 'permission_id', 'allowed'];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
