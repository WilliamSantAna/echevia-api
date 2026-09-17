<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertPlantRequest;
use App\Http\Resources\PlantResource;
use App\Models\Plant;
use App\Services\PlantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(): JsonResponse
    {
        $plants = Plant::query()
            ->with(['photos', 'videos'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'plants' => PlantResource::collection($plants)->resolve(),
        ]);
    }

    public function show(Plant $plant): JsonResponse
    {
        $plant->load(['photos', 'videos']);

        return response()->json(PlantResource::make($plant)->resolve());
    }

    public function store(UpsertPlantRequest $request, PlantService $plants): JsonResponse
    {
        $plant = $plants->upsert($request->validated());

        return response()->json(PlantResource::make($plant)->resolve(), 201);
    }

    public function update(UpsertPlantRequest $request, Plant $plant, PlantService $plants): JsonResponse
    {
        $plant = $plants->upsert($request->validated(), $plant);

        return response()->json(PlantResource::make($plant)->resolve());
    }

    public function destroy(Plant $plant): JsonResponse
    {
        $plant->delete();

        return response()->json(null, 204);
    }

    public function favorite(Request $request, Plant $plant): JsonResponse
    {
        $validated = $request->validate([
            'favorite' => ['required', 'boolean'],
        ]);

        $plant->favorite = $validated['favorite'];
        $plant->save();
        $plant->load(['photos', 'videos']);

        return response()->json(PlantResource::make($plant)->resolve());
    }
}
