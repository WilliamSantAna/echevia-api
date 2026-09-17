<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.r2.url' => 'https://r2.test']);
        Storage::fake('r2');
    }

    public function test_lists_plants_empty(): void
    {
        $this->getJson('/api/plants')
            ->assertOk()
            ->assertJson(['plants' => []]);
    }

    public function test_uploads_a_photo_to_r2(): void
    {
        $file = UploadedFile::fake()->create('lola.jpg', 80, 'image/jpeg');

        $response = $this->post('/api/media', [
            'file' => $file,
            'kind' => 'photo',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonStructure(['url', 'key']);

        $key = $response->json('key');
        $this->assertIsString($key);
        Storage::disk('r2')->assertExists($key);
        $this->assertStringStartsWith('https://r2.test/', (string) $response->json('url'));
    }

    public function test_rejects_video_uploaded_as_photo(): void
    {
        $file = UploadedFile::fake()->create('clip.mp4', 120, 'video/mp4');

        $this->post('/api/media', [
            'file' => $file,
            'kind' => 'photo',
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_creates_a_plant_with_photo_urls(): void
    {
        $payload = $this->plantPayload();

        $this->postJson('/api/plants', $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Lola')
            ->assertJsonPath('botanicalFamily', 'Crassulaceae')
            ->assertJsonPath('photos.0.isMain', true)
            ->assertJsonPath('photos.0.url', 'https://r2.test/plants/photo/a.jpg')
            ->assertJsonPath('videos.0.durationSeconds', 12);

        $this->assertDatabaseHas('plants', [
            'identification' => 'ECH-LOLA-01',
            'botanical_family' => 'Crassulaceae',
        ]);
        $this->assertDatabaseHas('plant_photos', [
            'url' => 'https://r2.test/plants/photo/a.jpg',
            'is_main' => 1,
        ]);
    }

    public function test_rejects_duplicate_identification(): void
    {
        $this->postJson('/api/plants', $this->plantPayload())->assertCreated();
        $this->postJson('/api/plants', $this->plantPayload([
            'id' => '8f6e2c3a-1b2d-4e5f-8a9b-0c1d2e3f4a5b',
            'name' => 'Outra',
        ]))->assertUnprocessable();
    }

    public function test_updates_plant_and_removes_dropped_photo_from_r2(): void
    {
        Storage::disk('r2')->put('plants/photo/old.jpg', 'old');
        Storage::disk('r2')->put('plants/photo/keep.jpg', 'keep');

        $created = $this->postJson('/api/plants', $this->plantPayload([
            'photos' => [
                [
                    'id' => '11111111-1111-4111-8111-111111111111',
                    'url' => 'https://r2.test/plants/photo/old.jpg',
                    'isMain' => true,
                    'key' => 'plants/photo/old.jpg',
                ],
                [
                    'id' => '22222222-2222-4222-8222-222222222222',
                    'url' => 'https://r2.test/plants/photo/keep.jpg',
                    'isMain' => false,
                    'key' => 'plants/photo/keep.jpg',
                ],
            ],
            'videos' => [],
        ]))->assertCreated();

        $id = $created->json('id');

        $this->postJson('/api/plants/'.$id, $this->plantPayload([
            'name' => 'Lola editada',
            'photos' => [
                [
                    'id' => '22222222-2222-4222-8222-222222222222',
                    'url' => 'https://r2.test/plants/photo/keep.jpg',
                    'isMain' => true,
                    'key' => 'plants/photo/keep.jpg',
                ],
            ],
            'videos' => [],
        ]))->assertOk()->assertJsonPath('name', 'Lola editada');

        Storage::disk('r2')->assertMissing('plants/photo/old.jpg');
        Storage::disk('r2')->assertExists('plants/photo/keep.jpg');
        $this->assertDatabaseCount('plant_photos', 1);
    }

    public function test_toggles_favorite_and_deletes_plant(): void
    {
        $created = $this->postJson('/api/plants', $this->plantPayload())->assertCreated();
        $id = $created->json('id');

        $this->postJson('/api/plants/'.$id.'/favorite', ['favorite' => true])
            ->assertOk()
            ->assertJsonPath('favorite', true);

        $this->postJson('/api/plants/'.$id.'/delete')->assertNoContent();
        $this->assertDatabaseCount('plants', 0);
    }

    public function test_accepts_legacy_seed_ids(): void
    {
        $this->postJson('/api/plants', $this->plantPayload([
            'id' => 'plant-lola',
            'identification' => 'ECH-LOLA-SEED',
            'photos' => [
                [
                    'id' => 'lola-1',
                    'url' => '/echevia/mock/echeveria-pot.jpg',
                    'isMain' => true,
                ],
            ],
            'videos' => [],
        ]))->assertCreated()->assertJsonPath('id', 'plant-lola');

        $this->getJson('/api/plants/plant-lola')
            ->assertOk()
            ->assertJsonPath('photos.0.id', 'lola-1');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function plantPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'name' => 'Lola',
            'species' => "Echeveria 'Lola'",
            'botanicalFamily' => 'Crassulaceae',
            'identification' => 'ECH-LOLA-01',
            'notes' => 'Roseta compacta',
            'favorite' => true,
            'createdAt' => '2026-03-12T10:15:00.000Z',
            'updatedAt' => '2026-08-02T18:40:00.000Z',
            'photos' => [
                [
                    'id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                    'url' => 'https://r2.test/plants/photo/a.jpg',
                    'isMain' => true,
                    'key' => 'plants/photo/a.jpg',
                ],
            ],
            'videos' => [
                [
                    'id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                    'url' => 'https://r2.test/plants/video/a.mp4',
                    'posterUrl' => 'https://r2.test/plants/photo/a.jpg',
                    'durationSeconds' => 12,
                    'key' => 'plants/video/a.mp4',
                ],
            ],
        ], $overrides);
    }
}
