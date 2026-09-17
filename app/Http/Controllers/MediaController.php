<?php

namespace App\Http\Controllers;

use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class MediaController extends Controller
{
    public function store(Request $request, MediaService $media): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'kind' => ['required', 'in:photo,video'],
        ], [
            'file.required' => 'Envie um arquivo.',
            'file.max' => 'O arquivo pode ter no máximo 20 MB.',
            'kind.in' => 'O tipo precisa ser photo ou video.',
        ]);

        $file = $validated['file'];
        $kind = $validated['kind'];

        if ($kind === 'photo' && ! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return response()->json(['message' => 'O arquivo precisa ser uma imagem.'], 422);
        }

        if ($kind === 'video' && ! str_starts_with((string) $file->getMimeType(), 'video/')) {
            return response()->json(['message' => 'O arquivo precisa ser um vídeo.'], 422);
        }

        try {
            $stored = $media->store($file, $kind);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Não foi possível enviar a mídia.'], 502);
        }

        return response()->json($stored, 201);
    }
}
