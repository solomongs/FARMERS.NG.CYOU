<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImport extends Model
{
    protected $fillable = [
        'uuid',
        'farm_id',
        'imported_by',
        'source',
        'source_farm_id',
        'source_farm_name',
        'file_hash',
        'archive_path',
        'source_version',
        'source_exported_at',
        'status',
        'counts',
        'warnings',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'source_version' => 'integer',
            'source_exported_at' => 'datetime',
            'counts' => 'array',
            'warnings' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function mappings()
    {
        return $this->hasMany(LegacyRecordMap::class);
    }
}
