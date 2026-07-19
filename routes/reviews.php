<?php

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Review Routes (Feature: Review & Rating System)
|--------------------------------------------------------------------------
|
| Owner: MD. Neamatullah Rahat
|
| Reviews are always scoped to a specific hotel, so routes are nested
| under /hotels/{hotel}/reviews rather than a flat /reviews resource.
| This keeps URLs self-descriptive (e.g. /hotels/12/reviews/create)
| and matches how the Hotel Details page will link into this feature.
|
*/

Route::middleware(['auth'])->group(function () {

    Route::prefix('hotels/{hotel}/reviews')->name('reviews.')->group(function () {

        // GET /hotels/{hotel}/reviews
        // Lists all reviews for a hotel + average rating summary
        Route::get('/', [ReviewController::class, 'index'])->name('index');

        // GET /hotels/{hotel}/reviews/create
        // Shows the "submit a review" form (only reachable if traveler
        // has a completed booking for this hotel — enforced in controller)
        Route::get('/create', [ReviewController::class, 'create'])->name('create');

        // POST /hotels/{hotel}/reviews
        // Stores a new review (rating + text + optional photos)
        Route::post('/', [ReviewController::class, 'store'])->name('store');

        // GET /hotels/{hotel}/reviews/{review}/edit
        // Shows edit form — only the review's author can reach this
        Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');

        // PUT /hotels/{hotel}/reviews/{review}
        // Updates an existing review
        Route::put('/{review}', [ReviewController::class, 'update'])->name('update');

        // DELETE /hotels/{hotel}/reviews/{review}
        // Deletes a review — author or Administrator only
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');

    });

});