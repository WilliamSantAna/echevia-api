<?php

namespace App\Services;

use App\Models\Plant;
use App\Models\PlantPhoto;
use App\Models\PlantVideo;
use Illuminate\Support\Carbon;

class PlantService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(array $payload, ?Plant $plant = null): Plant
    {
        $attributes = [
            'name' => $payload['name'],
            'species' => $payload['species'] ?? '',
            'botanical_family' => $payload['botanicalFamily'] ?? '',
            'identification' => $payload['identification'],
            'notes' => $payload['notes'] ?? '',
            'favorite' => (bool) ($payload['favorite'] ?? $plant?->favorite ?? false),
        ];

        if ($plant === null) {
            $plant = new Plant();
            if (! empty($payload['id'])) {
                $plant->id = $payload['id'];
            }
        }

        $plant->fill($attributes);

        if (! empty($payload['createdAt'])) {
            $plant->created_at = Carbon::parse($payload['createdAt']);
        }

        if (! empty($payload['updatedAt'])) {
            $plant->updated_at = Carbon::parse($payload['updatedAt']);
        }

        $preserveTimestamps = ! empty($payload['createdAt']) || ! empty($payload['updatedAt']);
        $plant->timestamps = ! $preserveTimestamps;
        $plant->save();
        $plant->timestamps = true;

        $this->syncPhotos($plant, $payload['photos'] ?? []);
        $this->syncVideos($plant, $payload['videos'] ?? []);

        return $plant->load(['photos', 'videos']);
    }

    /**
     * @param  list<array<string, mixed>>  $photos
     */
    private function syncPhotos(Plant $plant, array $photos): void
    {
        $keep = [];
        $existing = $plant->photos()->get()->keyBy('id');

        foreach (array_values($photos) as $index => $item) {
            $id = $item['id'] ?? null;
            $record = is_string($id) ? $existing->get($id) : null;
            $nextKey = $item['key'] ?? $record?->r2_key;

            if ($record && $record->r2_key && $record->r2_key !== $nextKey) {
                $record->deleteStoredObject();
            }

            $attributes = [
                'plant_id' => $plant->id,
                'url' => $item['url'],
                'r2_key' => $nextKey,
                'is_main' => (bool) ($item['isMain'] ?? false),
                'position' => $index,
            ];

            if (is_string($id) && $id !== '') {
                $saved = PlantPhoto::query()->updateOrCreate(['id' => $id], $attributes);
            } else {
                $saved = PlantPhoto::query()->create($attributes);
            }
            $keep[] = $saved->id;
        }

        foreach ($existing as $photo) {
            if (! in_array($photo->id, $keep, true)) {
                $photo->deleteStoredObject();
                $photo->delete();
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     */
    private function syncVideos(Plant $plant, array $videos): void
    {
        $keep = [];
        $existing = $plant->videos()->get()->keyBy('id');

        foreach (array_values($videos) as $index => $item) {
            $id = $item['id'] ?? null;
            $record = is_string($id) ? $existing->get($id) : null;
            $nextKey = $item['key'] ?? $record?->r2_key;
            $nextPosterKey = $item['posterKey'] ?? $record?->poster_r2_key;

            if ($record) {
                if ($record->r2_key && $record->r2_key !== $nextKey) {
                    Plant::deleteR2Key($record->r2_key);
                }
                if ($record->poster_r2_key && $record->poster_r2_key !== $nextPosterKey) {
                    Plant::deleteR2Key($record->poster_r2_key);
                }
            }

            $attributes = [
                'plant_id' => $plant->id,
                'url' => $item['url'],
                'r2_key' => $nextKey,
                'poster_url' => $item['posterUrl'] ?? '',
                'poster_r2_key' => $nextPosterKey,
                'duration_seconds' => (int) ($item['durationSeconds'] ?? 0),
                'position' => $index,
            ];

            if (is_string($id) && $id !== '') {
                $saved = PlantVideo::query()->updateOrCreate(['id' => $id], $attributes);
            } else {
                $saved = PlantVideo::query()->create($attributes);
            }
            $keep[] = $saved->id;
        }

        foreach ($existing as $video) {
            if (! in_array($video->id, $keep, true)) {
                $video->deleteStoredObjects();
                $video->delete();
            }
        }
    }
}
