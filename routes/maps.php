<?php

use App\Http\Controllers\MapsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Maps Routes (Feature: Interactive Maps & Nearby Attractions)
|--------------------------------------------------------------------------
|
| Owner: MD. Neamatullah Rahat
|
| These routes power the Google Maps integration on the hotel details page.
| A traveler must be authenticated to view hotel maps, since this is part
| of the booking research flow (auth middleware matches the rest of the
| traveler-facing routes in this project).
|
*/

Route::middleware(['auth'])->group(function () {

    // Renders the full map view (embedded inside hotel details page)
    // GET /hotels/{hotel}/map
    Route::get('/hotels/{hotel}/map', [MapsController::class, 'show'])
        ->name('maps.show');

    // AJAX endpoint: returns nearby attractions (restaurants, hospitals,
    // transport, shopping, tourist spots) as JSON for the map to plot
    // GET /hotels/{hotel}/map/nearby
    Route::get('/hotels/{hotel}/map/nearby', [MapsController::class, 'nearby'])
        ->name('maps.nearby');

    // AJAX endpoint: returns estimated travel distance/time between the
    // hotel and a place the traveler picks (e.g. airport, a landmark)
    // GET /hotels/{hotel}/map/distance
    Route::get('/hotels/{hotel}/map/distance', [MapsController::class, 'distance'])
        ->name('maps.distance');

});