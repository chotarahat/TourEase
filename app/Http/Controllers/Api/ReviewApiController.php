<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use App\Services\ReviewPhotoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * ReviewApiController
 * Owner: MD. Neamatullah Rahat
 * Assignment: 03 — REST API for Review & Rating System
 *
 * Public endpoints (no auth middleware) — auth is out of scope for this
 * individual assignment; user IDs are passed explicitly in requests as
 * a placeholder until the shared Login module supplies real auth.
 */
class ReviewApiController extends Controller
{
    public function __construct(protected ReviewPhotoUploadService $photoUploadService)
    {
    }

    /**
     * GET /api/hotels/{hotel}/reviews
     */
    public function index(string $hotel): JsonResponse
    {
        $hotelModel = Hotel::find($hotel);

        if (! $hotelModel) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found.',
            ], 404);
        }

        $reviews = Review::where('hotel_id', $hotel)->latest()->get();

        return response()->json([
            'success' => true,
            'hotel_id' => $hotel,
            'average_rating' => $hotelModel->averageRating(),
            'review_count' => $reviews->count(),
            'data' => $reviews,
        ], 200);
    }

    /**
     * POST /api/reviews
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hotel_id' => ['required', 'string'],
            'traveler_id' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $hotel = Hotel::find($validated['hotel_id']);
        if (! $hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found.',
            ], 404);
        }

        $hasCompletedStay = Booking::where('traveler_id', $validated['traveler_id'])
            ->where('hotel_id', $validated['hotel_id'])
            ->where('booking_status', 'Completed')
            ->exists();

        if (! $hasCompletedStay) {
            return response()->json([
                'success' => false,
                'message' => 'This traveler has no completed booking for this hotel.',
            ], 403);
        }

        $alreadyReviewed = Review::where('hotel_id', $validated['hotel_id'])
            ->where('traveler_id', $validated['traveler_id'])
            ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'success' => false,
                'message' => 'This traveler has already reviewed this hotel.',
            ], 409);
        }

        $photoPaths = $request->hasFile('photos')
            ? $this->photoUploadService->uploadMany($request->file('photos'))
            : [];

        $review = Review::create([
            'hotel_id' => $validated['hotel_id'],
            'traveler_id' => $validated['traveler_id'],
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'images' => $photoPaths,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully.',
            'data' => $review,
        ], 201);
    }

    /**
     * PUT /api/reviews/{review}
     */
    public function update(Request $request, string $review): JsonResponse
    {
        $reviewModel = Review::find($review);

        if (! $reviewModel) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'traveler_id' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if ($reviewModel->traveler_id !== $validated['traveler_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this review.',
            ], 403);
        }

        $photoPaths = $request->hasFile('photos')
            ? $this->photoUploadService->uploadMany($request->file('photos'))
            : $reviewModel->images;

        $reviewModel->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'images' => $photoPaths,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data' => $reviewModel->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/reviews/{review}
     */
    public function destroy(Request $request, string $review): JsonResponse
    {
        $reviewModel = Review::find($review);

        if (! $reviewModel) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'traveler_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $travelerId = $validator->validated()['traveler_id'];

        if ($reviewModel->traveler_id !== $travelerId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this review.',
            ], 403);
        }

        $this->photoUploadService->deleteMany($reviewModel->images);
        $reviewModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ], 200);
    }
}
