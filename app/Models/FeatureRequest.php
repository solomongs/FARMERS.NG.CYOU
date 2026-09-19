<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureRequest extends Model
{
    protected $fillable = [
        'farm_id', 'user_id', 'title', 'category', 'description', 'problem',
        'suggested_solution', 'priority', 'status', 'attachment_path',
        'contact_permission',
    ];

    protected function casts(): array
    {
        return ['contact_permission' => 'boolean'];
    }
}
