<?php

namespace App\Services;

use App\Exceptions\PlantNetException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class PlantNetService
{
    /**
     * @return list<array{score: float, scientificName: string, commonName: string, family: string, imageUrl: string|null}>
     */
    public function identify(UploadedFile $image): array
    {
        $apiKey = config('services.plantnet.key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new PlantNetException('A chave da API Pl@ntNet não está configurada.', 500);
        }

        $apiKey = trim($apiKey);

        $filename = $image->getClientOriginalName() ?: 'planta.jpg';
        $path = $image->getRealPath();

        if ($path === false) {
            throw new PlantNetException('Não foi possível ler a foto enviada.', 422);
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new PlantNetException('Não foi possível ler a foto enviada.', 422);
        }

        $query = http_build_query([
            'api-key' => $apiKey,
            'lang' => 'pt',
            'nb-results' => 8,
            'include-related-images' => 'true',
        ]);

        $origin = (string) config('services.plantnet.origin');

        $response = Http::timeout(45)
            ->withHeaders($origin !== '' ? ['Origin' => $origin] : [])
            ->attach('images', $contents, $filename)
            ->post('https://my-api.plantnet.org/v2/identify/all?'.$query, [
                'organs' => 'auto',
            ]);

        if ($response->status() === 404) {
            throw new PlantNetException(
                'Não encontramos uma espécie nesta foto. Tente outra imagem, de preferência da folha ou da flor.',
                404,
            );
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new PlantNetException($this->authFailureMessage($response->json('message')), $response->status());
        }

        if ($response->status() === 429) {
            throw new PlantNetException('Limite de identificações da Pl@ntNet atingido. Tente mais tarde.', 429);
        }

        if ($response->failed()) {
            throw new PlantNetException(
                $response->json('message') ?: 'A Pl@ntNet não conseguiu identificar esta foto.',
                $response->serverError() ? 502 : $response->status(),
            );
        }

        $matches = [];

        foreach ($response->json('results') ?? [] as $result) {
            $species = $result['species'] ?? [];
            $scientificName = $species['scientificNameWithoutAuthor']
                ?? $species['scientificName']
                ?? '';

            if ($scientificName === '') {
                continue;
            }

            $commonNames = $species['commonNames'] ?? [];
            $family = $species['family'] ?? [];
            $related = $result['images'][0]['url'] ?? null;

            $matches[] = [
                'score' => (float) ($result['score'] ?? 0),
                'scientificName' => $scientificName,
                'commonName' => $commonNames[0] ?? $scientificName,
                'family' => $family['scientificNameWithoutAuthor'] ?? $family['scientificName'] ?? '',
                'imageUrl' => $this->relatedImageUrl($related),
            ];
        }

        if ($matches === []) {
            throw new PlantNetException(
                'Não encontramos uma espécie nesta foto. Tente outra imagem, de preferência da folha ou da flor.',
                404,
            );
        }

        return $matches;
    }

    private function relatedImageUrl(mixed $url): ?string
    {
        if (is_string($url) && $url !== '') {
            return $url;
        }

        if (! is_array($url)) {
            return null;
        }

        foreach (['m', 's', 'o'] as $size) {
            if (! empty($url[$size]) && is_string($url[$size])) {
                return $url[$size];
            }
        }

        return null;
    }

    private function authFailureMessage(mixed $message): string
    {
        $text = is_string($message) ? strtolower($message) : '';

        if (str_contains($text, 'remote ip not allowed')) {
            return 'A Pl@ntNet bloqueou o IP deste servidor. Como a identificação passa pelo backend, desmarque “Expose my API key” ou autorize o IPv4 da hospedagem em Authorized IPs.';
        }

        if (str_contains($text, 'origin not allowed')) {
            return 'A Pl@ntNet recusou a origem. Em Authorized domains use exatamente https://marketingcriativa.com.br/ (com a barra no final).';
        }

        return is_string($message) && $message !== ''
            ? $message
            : 'A chave da API Pl@ntNet foi recusada.';
    }
}
