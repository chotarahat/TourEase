<?php

use App\Http\Controllers\Api\ReviewApiController;
use App\Http\Controllers\Api\WishlistApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Review & Rating API Routes
|--------------------------------------------------------------------------
| Owner: MD. Neamatullah Rahat
| Assignment: 03 — API Testing (Postman)
|
| Left intentionally PUBLIC (no auth middleware) per assignment scope:
| authentication is handled by the team's shared Login/Auth module,
| which is outside this individual assignment.
*/

Route::get('/hotels/{hotel}/reviews', [ReviewApiController::class, 'index']);
Route::post('/reviews', [ReviewApiController::class, 'store']);
Route::put('/reviews/{review}', [ReviewApiController::class, 'update']);
Route::delete('/reviews/{review}', [ReviewApiController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Wishlist API Routes
|--------------------------------------------------------------------------
| Owner: MD. Neamatullah Rahat
| Assignment: 03 — API Testing (Postman)
*/

Route::get('/users/{user}/wishlist', [WishlistApiController::class, 'index']);
Route::post('/wishlist', [WishlistApiController::class, 'store']);
Route::delete('/wishlist/{wishlist}', [WishlistApiController::class, 'destroy']);
