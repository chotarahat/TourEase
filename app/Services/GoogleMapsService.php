<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GoogleMapsService
 *
 * Owner: MD. Neamatullah Rahat
 * Feature: Interactive Maps & Nearby Attractions
 *
 * Responsibility: The ONLY place in the codebase that talks to Google's
 * APIs for this feature. MapsController never calls Http::get() directly —
 * it calls this service. This isolation means:
 *   (a) if Google's API changes, only this file needs updating
 *   (b) this class can be unit-tested by mocking Http::fake()
 *   (c) matches the project's established Services/ pattern
 *       (StripeService, GmailService, OpenWeatherService, OpenAIService)
 */
class GoogleMapsService
{
    /**
     * Base URL for Google Places API (Nearby Search - legacy stable endpoint).
     */
    protected string $placesBaseUrl = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';

    /**
     * Base URL for Google Distance Matrix API.
     */
    protected string $distanceBaseUrl = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    /**
     * The API key, pulled once from config (never hardcoded).
     */
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key');
    }

    /**
     * Fetch nearby places of a given type around a coordinate.
     *
     * @param  float   $latitude
     * @param  float   $longitude
     * @param  string  $type      Google Places type: restaurant, hospital,
     *                            transit_station, shopping_mall, tourist_attraction
     * @param  int     $radius    Search radius in meters
     * @return array<int, array{name: string, address: string, rating: ?float, lat: float, lng: float, place_id: string}>
     */
    public function getNearbyPlaces(float $latitude, float $longitude, string $type, int $radius = 2000): array
    {
        $response = Http::timeout(10)->get($this->placesBaseUrl, [
            'location' => "{$latitude},{$longitude}",
            'radius' => $radius,
            'type' => $type,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('GoogleMapsService: Nearby Places request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();

        // Google returns 'ZERO_RESULTS' as a valid, non-error status —
        // must be handled separately from an actual API failure.
        if (($data['status'] ?? null) !== 'OK') {
            if (($data['status'] ?? null) !== 'ZERO_RESULTS') {
                Log::warning('GoogleMapsService: Places API returned non-OK status', [
                    'status' => $data['status'] ?? 'UNKNOWN',
                    'error_message' => $data['error_message'] ?? null,
                ]);
            }
            return [];
        }

        // Map Google's verbose response into a clean, minimal shape —
        // the frontend only needs these fields, not Google's full payload.
        return collect($data['results'] ?? [])->map(function (array $place) {
            return [
                'name' => $place['name'] ?? 'Unnamed',
                'address' => $place['vicinity'] ?? 'Address unavailable',
                'rating' => $place['rating'] ?? null,
                'lat' => $place['geometry']['location']['lat'] ?? null,
                'lng' => $place['geometry']['location']['lng'] ?? null,
                'place_id' => $place['place_id'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * Calculate travel distance and duration between two coordinates.
     *
     * @param  float   $originLat
     * @param  float   $originLng
     * @param  float   $destLat
     * @param  float   $destLng
     * @param  string  $mode  driving | walking | transit
     * @return array{distance: string, duration: string}
     */
    public function getDistance(float $originLat, float $originLng, float $destLat, float $destLng, string $mode = 'driving'): array
    {
        $response = Http::timeout(10)->get($this->distanceBaseUrl, [
            'origins' => "{$originLat},{$originLng}",
            'destinations' => "{$destLat},{$destLng}",
            'mode' => $mode,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('GoogleMapsService: Distance Matrix request failed', [
                'status' => $response->status(),
            ]);
            return ['distance' => 'N/A', 'duration' => 'N/A'];
        }

        $data = $response->json();
        $element = $data['rows'][0]['elements'][0] ?? null;

        if (! $element || $element['status'] !== 'OK') {
            return ['distance' => 'N/A', 'duration' => 'N/A'];
        }

        return [
            'distance' => $element['distance']['text'] ?? 'N/A', // e.g. "4.2 km"
            'duration' => $element['duration']['text'] ?? 'N/A', // e.g. "12 mins"
        ];
    }
}