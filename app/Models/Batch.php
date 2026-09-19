<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_id', 'created_by', 'batch_number', 'production_type', 'breed',
        'date_in', 'initial_birds', 'current_birds', 'purchase_cost',
        'supplier', 'source', 'expected_cycle_days', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_in' => 'date',
            'purchase_cost' => 'decimal:2',
            'initial_birds' => 'integer',
            'current_birds' => 'integer',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
