<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyRecordMap extends Model
{
    protected $fillable = [
        'legacy_import_id', 'farm_id', 'store_name', 'legacy_id',
        'target_table', 'target_id', 'status', 'note',
    ];
}
