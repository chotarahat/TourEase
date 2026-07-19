<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * WishlistApiController
 * Owner: MD. Neamatullah Rahat
 * Feature: Wishlist & Saved Trips (Module 3)
 * Assignment: 03 — REST API for Wishlist
 *
 * Public endpoints — same scope decision as ReviewApiController.
 */
class WishlistApiController extends Controller
{
    /**
     * GET /api/users/{user}/wishlist
     */
    public function index(string $user): JsonResponse
    {
        $userModel = User::find($user);

        if (! $userModel) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $wishlist = Wishlist::with('hotel')
            ->where('traveler_id', $user)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'user_id' => $user,
            'count' => $wishlist->count(),
            'data' => $wishlist,
        ], 200);
    }

    /**
     * POST /api/wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'traveler_id' => ['required', 'string'],
            'hotel_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $userExists = User::find($validated['traveler_id']);
        if (! $userExists) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $hotelExists = Hotel::find($validated['hotel_id']);
        if (! $hotelExists) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found.',
            ], 404);
        }

        $alreadyExists = Wishlist::where('traveler_id', $validated['traveler_id'])
            ->where('hotel_id', $validated['hotel_id'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'This hotel is already in the wishlist.',
            ], 409);
        }

        // Defensive: unique compound index (see migration) is the real
        // safety net against race conditions — caught here as a clean 409.
        try {
            $wishlistEntry = Wishlist::create([
                'traveler_id' => $validated['traveler_id'],
                'hotel_id' => $validated['hotel_id'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'This hotel is already in the wishlist.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotel added to wishlist.',
            'data' => $wishlistEntry,
        ], 201);
    }

    /**
     * DELETE /api/wishlist/{wishlist}
     */
    public function destroy(Request $request, string $wishlist): JsonResponse
    {
        $wishlistEntry = Wishlist::find($wishlist);

        if (! $wishlistEntry) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist entry not found.',
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

        if ($wishlistEntry->traveler_id !== $travelerId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to remove this wishlist entry.',
            ], 403);
        }

        $wishlistEntry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hotel removed from wishlist.',
        ], 200);
    }
}
