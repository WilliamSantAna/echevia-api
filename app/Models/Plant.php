<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Plant extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'species',
        'botanical_family',
        'identification',
        'notes',
        'favorite',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'favorite' => 'boolean',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PlantPhoto::class)->orderBy('position');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(PlantVideo::class)->orderBy('position');
    }

    protected static function booted(): void
    {
        static::creating(function (Plant $plant) {
            if (empty($plant->id)) {
                $plant->id = (string) Str::uuid();
            }
        });

        static::deleting(function (Plant $plant) {
            $plant->loadMissing(['photos', 'videos']);

            foreach ($plant->photos as $photo) {
                $photo->deleteStoredObject();
            }

            foreach ($plant->videos as $video) {
                $video->deleteStoredObjects();
            }
        });
    }

    public static function deleteR2Key(?string $key): void
    {
        if ($key === null || $key === '') {
            return;
        }

        Storage::disk('r2')->delete($key);
    }
}
