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
                    [
                        'score' => 0.44,
                        'species' => [
                            'scientificNameWithoutAuthor' => 'Echeveria peacockii',
                            'commonNames' => ['Echeveria pavão'],
                            'family' => ['scientificNameWithoutAuthor' => 'Crassulaceae'],
                        ],
                        'images' => [],
                    ],
                    [
                        'score' => 0.12,
                        'species' => [
                            'scientificNameWithoutAuthor' => 'Sedum morganianum',
                            'commonNames' => ['Rabo de burro'],
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
            ->assertJsonPath('matches.0.family', 'Crassulaceae')
            ->assertJsonPath('matches.1.scientificName', 'Echeveria peacockii')
            ->assertJsonCount(2, 'matches');
    }

    public function test_identify_explains_plantnet_ip_block(): void
    {
        Http::fake([
            'my-api.plantnet.org/*' => Http::response([
                'message' => 'error: remote IP not allowed',
            ], 403),
        ]);

        $file = UploadedFile::fake()->create('planta.jpg', 120, 'image/jpeg');

        $this->post('/api/identify', ['image' => $file], [
            'Accept' => 'application/json',
        ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'A PlantNet bloqueou o IP deste servidor. Como a identificação passa pelo backend, desmarque “Expose my API key” ou autorize o IPv4 da hospedagem em Authorized IPs.',
            );
    }

    public function test_identify_sends_site_origin_to_plantnet(): void
    {
        Http::fake([
            'my-api.plantnet.org/*' => Http::response([
                'results' => [
                    [
                        'score' => 0.8,
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
        ])->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'my-api.plantnet.org')
                && $request->hasHeader('Origin', 'https://marketingcriativa.com.br/');
        });
    }
}
