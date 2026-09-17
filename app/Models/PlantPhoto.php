<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantPhoto extends Model
{
    use HasUuids;

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

    public function deleteStoredObject(): void
    {
        Plant::deleteR2Key($this->r2_key);
    }
}
