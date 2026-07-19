<?php

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Review Routes (Feature: Review & Rating System — Web/Blade version)
|--------------------------------------------------------------------------
| Owner: MD. Neamatullah Rahat
*/

Route::middleware(['auth'])->group(function () {

    Route::prefix('hotels/{hotel}/reviews')->name('reviews.')->group(function () {

        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/create', [ReviewController::class, 'create'])->name('create');
        Route::post('/', [ReviewController::class, 'store'])->name('store');
        Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
        Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');

    });

});
