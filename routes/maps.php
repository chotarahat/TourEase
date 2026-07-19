<?php

use App\Http\Controllers\MapsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Maps Routes (Feature: Interactive Maps & Nearby Attractions)
|--------------------------------------------------------------------------
| Owner: MD. Neamatullah Rahat
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/hotels/{hotel}/map', [MapsController::class, 'show'])
        ->name('maps.show');

    Route::get('/hotels/{hotel}/map/nearby', [MapsController::class, 'nearby'])
        ->name('maps.nearby');

    Route::get('/hotels/{hotel}/map/distance', [MapsController::class, 'distance'])
        ->name('maps.distance');

});
