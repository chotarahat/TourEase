<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use App\Services\ReviewPhotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ReviewController
 *
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 *
 * Responsibility: Full CRUD for hotel reviews. Enforces the business rule
 * that only travelers with a COMPLETED booking for a hotel may review it,
 * and that only a review's author (or an Administrator) may edit/delete it.
 * Photo upload logic is delegated to ReviewPhotoUploadService, keeping this
 * controller focused on orchestration, not file-handling details.
 */
class ReviewController extends Controller
{
    public function __construct(protected ReviewPhotoUploadService $photoUploadService)
    {
    }

    /**
     * List all reviews for a hotel, plus an average rating summary.
     *
     * Route: GET /hotels/{hotel}/reviews  (name: reviews.index)
     */
    public function index(Hotel $hotel): View
    {
        $reviews = Review::where('hotel_id', $hotel->id)
            ->latest()
            ->paginate(10);

        return view('reviews.index', [
            'hotel' => $hotel,
            'reviews' => $reviews,
            'averageRating' => $hotel->averageRating(),
        ]);
    }

    /**
     * Show the "submit a review" form — but only if the traveler
     * actually has a completed booking at this hotel.
     *
     * Route: GET /hotels/{hotel}/reviews/create  (name: reviews.create)
     */
    public function create(Hotel $hotel): View|RedirectResponse
    {
        if (! $this->travelerHasCompletedStay($hotel)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You can only review hotels after completing a stay there.');
        }

        // Prevent submitting a second review for the same hotel
        $alreadyReviewed = Review::where('hotel_id', $hotel->id)
            ->where('traveler_id', Auth::id())
            ->exists();

        if ($alreadyReviewed) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You have already reviewed this hotel.');
        }

        return view('reviews.create', ['hotel' => $hotel]);
    }

    /**
     * Store a newly submitted review.
     *
     * Route: POST /hotels/{hotel}/reviews  (name: reviews.store)
     */
    public function store(StoreReviewRequest $request, Hotel $hotel): RedirectResponse
    {
        // Re-check eligibility server-side even though create() already
        // gated the form — never trust that a POST only came from our own form.
        if (! $this->travelerHasCompletedStay($hotel)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You can only review hotels after completing a stay there.');
        }

        $validated = $request->validated();

        // Photo upload is delegated entirely to the service —
        // controller doesn't know or care about storage disk details.
        $photoPaths = $request->hasFile('photos')
            ? $this->photoUploadService->uploadMany($request->file('photos'))
            : [];

        Review::create([
            'hotel_id' => $hotel->id,
            'traveler_id' => Auth::id(),
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'images' => $photoPaths,
        ]);

        return redirect()
            ->route('reviews.index', $hotel)
            ->with('success', 'Your review has been submitted. Thank you!');
    }

    /**
     * Show the edit form for a review — author only.
     *
     * Route: GET /hotels/{hotel}/reviews/{review}/edit  (name: reviews.edit)
     */
    public function edit(Hotel $hotel, Review $review): View|RedirectResponse
    {
        if (! $this->isReviewOwner($review)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You are not authorized to edit this review.');
        }

        return view('reviews.edit', ['hotel' => $hotel, 'review' => $review]);
    }

    /**
     * Update an existing review — author only.
     *
     * Route: PUT /hotels/{hotel}/reviews/{review}  (name: reviews.update)
     */
    public function update(StoreReviewRequest $request, Hotel $hotel, Review $review): RedirectResponse
    {
        if (! $this->isReviewOwner($review)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You are not authorized to update this review.');
        }

        $validated = $request->validated();

        // Only replace photos if new ones were uploaded — otherwise
        // keep the existing images untouched.
        $photoPaths = $request->hasFile('photos')
            ? $this->photoUploadService->uploadMany($request->file('photos'))
            : $review->images;

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'images' => $photoPaths,
        ]);

        return redirect()
            ->route('reviews.index', $hotel)
            ->with('success', 'Your review has been updated.');
    }

    /**
     * Delete a review — author or Administrator only.
     *
     * Route: DELETE /hotels/{hotel}/reviews/{review}  (name: reviews.destroy)
     */
    public function destroy(Hotel $hotel, Review $review): RedirectResponse
    {
        $isAdmin = Auth::user()->role === 'admin';

        if (! $this->isReviewOwner($review) && ! $isAdmin) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You are not authorized to delete this review.');
        }

        $this->photoUploadService->deleteMany($review->images);
        $review->delete();

        return redirect()
            ->route('reviews.index', $hotel)
            ->with('success', 'Review deleted.');
    }

    /**
     * Business rule: a traveler may only review a hotel they've
     * actually completed a stay at.
     *
     * NOTE: This queries the Booking model (owned by Showbhik) read-only.
     * Assumes Booking has: traveler_id, hotel_id, booking_status = 'Completed'.
     * If Showbhik's actual field names differ, only this one query needs updating.
     */
    private function travelerHasCompletedStay(Hotel $hotel): bool
    {
        return Booking::where('traveler_id', Auth::id())
            ->where('hotel_id', $hotel->id)
            ->where('booking_status', 'Completed')
            ->exists();
    }

    /**
     * Authorization helper: is the current user the review's author?
     */
    private function isReviewOwner(Review $review): bool
    {
        return $review->traveler_id === Auth::id();
    }
}