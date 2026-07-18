<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\GoogleMapsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MapsController
 *
 * Owner: MD. Neamatullah Rahat
 * Feature: Interactive Maps & Nearby Attractions (Google Maps API)
 *
 * Responsibility: Handles all HTTP requests related to displaying a hotel's
 * location on an interactive map, fetching nearby points of interest
 * (restaurants, hospitals, transport, shopping, tourist attractions), and
 * calculating travel distances. Contains NO direct Google API logic —
 * that all lives in GoogleMapsService, keeping this controller thin and
 * testable (Laravel best practice: controllers orchestrate, services do work).
 */
class MapsController extends Controller
{
    /**
     * The service that talks to Google Maps / Places / Distance Matrix APIs.
     * Injected via Laravel's service container (constructor injection).
     */
    public function __construct(protected GoogleMapsService $mapsService)
    {
    }

    /**
     * Display the interactive map page for a specific hotel.
     *
     * Route: GET /hotels/{hotel}/map  (name: maps.show)
     *
     * @param  Hotel  $hotel  Resolved automatically via route model binding
     */
    public function show(Hotel $hotel): View|RedirectResponse
    {
        // Guard clause: a hotel without coordinates cannot be mapped.
        // Redirect back with a clear error instead of showing a broken map.
        if (! $hotel->latitude || ! $hotel->longitude) {
            return redirect()
                ->back()
                ->with('error', 'This hotel does not have location data available yet.');
        }

        // Pass only what the view needs: id, name, and coordinates.
        // We do NOT pass the full $hotel object with unrelated fields
        // (pricing, owner_id, etc.) to keep this view's data contract clean.
        return view('maps.show', [
            'hotel' => $hotel,
            'googleMapsApiKey' => config('services.google_maps.key'),
        ]);
    }

    /**
     * Return nearby attractions/facilities around a hotel as JSON.
     * Called via fetch() from maps.js after the map has loaded.
     *
     * Route: GET /hotels/{hotel}/map/nearby  (name: maps.nearby)
     *
     * Query params:
     *   type = restaurant | hospital | transit_station | shopping_mall | tourist_attraction
     *   radius = search radius in meters (default 2000)
     */
    public function nearby(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:restaurant,hospital,transit_station,shopping_mall,tourist_attraction',
            'radius' => 'nullable|integer|min:100|max:20000',
        ]);

        if (! $hotel->latitude || ! $hotel->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel location not set.',
            ], 422);
        }

        $places = $this->mapsService->getNearbyPlaces(
            latitude: $hotel->latitude,
            longitude: $hotel->longitude,
            type: $validated['type'],
            radius: $validated['radius'] ?? 2000,
        );

        return response()->json([
            'success' => true,
            'type' => $validated['type'],
            'count' => count($places),
            'places' => $places,
        ]);
    }

    /**
     * Calculate estimated travel distance and duration between the hotel
     * and a destination point picked by the traveler (e.g. airport).
     *
     * Route: GET /hotels/{hotel}/map/distance  (name: maps.distance)
     *
     * Query params:
     *   destination_lat, destination_lng = coordinates traveler selected
     *   mode = driving | walking | transit (default driving)
     */
    public function distance(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
            'mode' => 'nullable|string|in:driving,walking,transit',
        ]);

        if (! $hotel->latitude || ! $hotel->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel location not set.',
            ], 422);
        }

        $result = $this->mapsService->getDistance(
            originLat: $hotel->latitude,
            originLng: $hotel->longitude,
            destLat: $validated['destination_lat'],
            destLng: $validated['destination_lng'],
            mode: $validated['mode'] ?? 'driving',
        );

        return response()->json([
            'success' => true,
            'distance' => $result['distance'],
            'duration' => $result['duration'],
            'mode' => $validated['mode'] ?? 'driving',
        ]);
    }
}