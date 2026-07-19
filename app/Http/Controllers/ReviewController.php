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
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System (Web/Blade version)
 */
class ReviewController extends Controller
{
    public function __construct(protected ReviewPhotoUploadService $photoUploadService)
    {
    }

    public function index(Hotel $hotel): View
    {
        $reviews = Review::where('hotel_id', $hotel->id)->latest()->paginate(10);

        return view('reviews.index', [
            'hotel' => $hotel,
            'reviews' => $reviews,
            'averageRating' => $hotel->averageRating(),
        ]);
    }

    public function create(Hotel $hotel): View|RedirectResponse
    {
        if (! $this->travelerHasCompletedStay($hotel)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You can only review hotels after completing a stay there.');
        }

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

    public function store(StoreReviewRequest $request, Hotel $hotel): RedirectResponse
    {
        if (! $this->travelerHasCompletedStay($hotel)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You can only review hotels after completing a stay there.');
        }

        $validated = $request->validated();

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

    public function edit(Hotel $hotel, Review $review): View|RedirectResponse
    {
        if (! $this->isReviewOwner($review)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You are not authorized to edit this review.');
        }

        return view('reviews.edit', ['hotel' => $hotel, 'review' => $review]);
    }

    public function update(StoreReviewRequest $request, Hotel $hotel, Review $review): RedirectResponse
    {
        if (! $this->isReviewOwner($review)) {
            return redirect()
                ->route('reviews.index', $hotel)
                ->with('error', 'You are not authorized to update this review.');
        }

        $validated = $request->validated();

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
     * Reads Booking model (owned by Showbhik) read-only.
     * Assumes: traveler_id, hotel_id, booking_status = 'Completed'.
     */
    private function travelerHasCompletedStay(Hotel $hotel): bool
    {
        return Booking::where('traveler_id', Auth::id())
            ->where('hotel_id', $hotel->id)
            ->where('booking_status', 'Completed')
            ->exists();
    }

    private function isReviewOwner(Review $review): bool
    {
        return $review->traveler_id === Auth::id();
    }
}