<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaService
{
    /**
     * @return array{url: string, key: string}
     */
    public function store(UploadedFile $file, string $kind): array
    {
        $extension = strtolower((string) $file->guessExtension())
            ?: strtolower((string) $file->getClientOriginalExtension())
            ?: 'bin';
        $key = 'plants/'.$kind.'/'.Str::uuid().'.'.$extension;
        $stored = Storage::disk('r2')->putFileAs(
            dirname($key),
            $file,
            basename($key),
            ['visibility' => 'private'],
        );

        if ($stored === false) {
            throw new RuntimeException('Não foi possível gravar o arquivo no R2.');
        }

        $base = rtrim((string) config('filesystems.disks.r2.url'), '/');
        if ($base === '') {
            throw new RuntimeException('R2_URL não está configurada.');
        }

        return [
            'url' => $base.'/'.$stored,
            'key' => $stored,
        ];
    }
}
