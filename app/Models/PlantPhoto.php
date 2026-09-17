<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlantPhoto extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'plant_id',
        'url',
        'r2_key',
        'is_main',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PlantPhoto $photo) {
            if (empty($photo->id)) {
                $photo->id = (string) Str::uuid();
            }
        });
    }

    public function deleteStoredObject(): void
    {
        Plant::deleteR2Key($this->r2_key);
    }
}
