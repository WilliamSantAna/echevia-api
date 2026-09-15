<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdentifyTest extends TestCase
{
    public function test_identify_requires_an_image(): void
    {
        $this->postJson('/api/identify')->assertUnprocessable();
    }

    public function test_identify_proxies_plantnet_matches(): void
    {
        Http::fake([
            'my-api.plantnet.org/*' => Http::response([
                'results' => [
                    [
                        'score' => 0.91,
                        'species' => [
                            'scientificNameWithoutAuthor' => 'Echeveria elegans',
                            'commonNames' => ['Rosa de alabastro'],
                            'family' => ['scientificNameWithoutAuthor' => 'Crassulaceae'],
                        ],
                        'images' => [],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('planta.jpg', 120, 'image/jpeg');

        $this->post('/api/identify', ['image' => $file], [
            'Accept' => 'application/json',
        ])
            ->assertOk()
            ->assertJsonPath('matches.0.scientificName', 'Echeveria elegans')
            ->assertJsonPath('matches.0.family', 'Crassulaceae');
    }
}
