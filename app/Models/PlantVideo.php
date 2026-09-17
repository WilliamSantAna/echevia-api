<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlantVideo extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'plant_id',
        'url',
        'r2_key',
        'poster_url',
        'poster_r2_key',
        'duration_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PlantVideo $video) {
            if (empty($video->id)) {
                $video->id = (string) Str::uuid();
            }
        });
    }

    public function deleteStoredObjects(): void
    {
        Plant::deleteR2Key($this->r2_key);
        Plant::deleteR2Key($this->poster_r2_key);
    }
}
