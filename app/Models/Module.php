<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'key', 'name', 'description', 'version', 'is_core', 'enabled', 'dependencies',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'enabled' => 'boolean',
            'dependencies' => 'array',
        ];
    }
}
