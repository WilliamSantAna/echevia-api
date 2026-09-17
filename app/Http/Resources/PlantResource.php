<?php

namespace App\Http\Resources;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plant
 */
class PlantResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'species' => $this->species ?? '',
            'botanicalFamily' => $this->botanical_family ?? '',
            'identification' => $this->identification,
            'notes' => $this->notes ?? '',
            'favorite' => (bool) $this->favorite,
            'photos' => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'isMain' => (bool) $photo->is_main,
                'key' => $photo->r2_key,
            ])->values()->all(),
            'videos' => $this->videos->map(fn ($video) => [
                'id' => $video->id,
                'url' => $video->url,
                'posterUrl' => $video->poster_url ?? '',
                'durationSeconds' => (int) $video->duration_seconds,
                'key' => $video->r2_key,
                'posterKey' => $video->poster_r2_key,
            ])->values()->all(),
            'createdAt' => optional($this->created_at)?->toIso8601String() ?? '',
            'updatedAt' => optional($this->updated_at)?->toIso8601String() ?? '',
        ];
    }
}
