<?php

namespace App\Http\Controllers;

use App\Exceptions\PlantNetException;
use App\Services\PlantNetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class IdentifyController extends Controller
{
    public function __invoke(Request $request, PlantNetService $plantNet): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ], [
            'image.required' => 'Envie uma foto da planta.',
            'image.file' => 'Envie uma foto da planta.',
            'image.image' => 'O arquivo precisa ser uma imagem (jpg ou png).',
            'image.max' => 'A foto pode ter no máximo 10 MB.',
        ]);

        try {
            $matches = $plantNet->identify($validated['image']);
        } catch (PlantNetException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Não foi possível identificar a espécie.',
            ], 502);
        }

        return response()->json([
            'matches' => $matches,
        ]);
    }
}
