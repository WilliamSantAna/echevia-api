<?php

use App\Http\Controllers\IdentifyController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PlantController;
use Illuminate\Support\Facades\Route;

Route::post('/identify', IdentifyController::class)
    ->middleware('throttle:20,1');

Route::post('/media', [MediaController::class, 'store'])
    ->middleware('throttle:30,1');

Route::get('/storage', [PlantController::class, 'storage']);

Route::get('/plants', [PlantController::class, 'index']);
Route::post('/plants', [PlantController::class, 'store'])->middleware('throttle:60,1');
Route::get('/plants/{plant}', [PlantController::class, 'show']);
Route::post('/plants/{plant}/favorite', [PlantController::class, 'favorite'])->middleware('throttle:60,1');
Route::patch('/plants/{plant}/favorite', [PlantController::class, 'favorite'])->middleware('throttle:60,1');
Route::post('/plants/{plant}/delete', [PlantController::class, 'destroy'])->middleware('throttle:60,1');
Route::delete('/plants/{plant}', [PlantController::class, 'destroy'])->middleware('throttle:60,1');
Route::post('/plants/{plant}', [PlantController::class, 'update'])->middleware('throttle:60,1');
Route::put('/plants/{plant}', [PlantController::class, 'update'])->middleware('throttle:60,1');
