<?php

use App\Http\Controllers\IdentifyController;
use Illuminate\Support\Facades\Route;

Route::post('/identify', IdentifyController::class)
    ->middleware('throttle:20,1');
