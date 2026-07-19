<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * MapsControllerTest
 * Owner: MD. Neamatullah Rahat
 */
class MapsControllerTest extends TestCase
{
    public function test_map_page_loads_for_hotel_with_coordinates(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create([
            'latitude' => 23.7808875,
            'longitude' => 90.2792371,
        ]);

        $response = $this->actingAs($user)->get(route('maps.show', $hotel));

        $response->assertStatus(200);
        $response->assertViewIs('maps.show');
        $response->assertViewHas('hotel', function ($viewHotel) use ($hotel) {
            return $viewHotel->id === $hotel->id;
        });
    }

    public function test_map_page_redirects_for_hotel_without_coordinates(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->actingAs($user)->get(route('maps.show', $hotel));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_nearby_endpoint_returns_places(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/place/*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'name' => 'Test Restaurant',
                        'vicinity' => '123 Test Street',
                        'rating' => 4.5,
                        'geometry' => ['location' => ['lat' => 23.78, 'lng' => 90.27]],
                        'place_id' => 'abc123',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $hotel = Hotel::factory()->create([
            'latitude' => 23.7808875,
            'longitude' => 90.2792371,
        ]);

        $response = $this->actingAs($user)->getJson(
            route('maps.nearby', $hotel) . '?type=restaurant'
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'count' => 1]);
        $response->assertJsonPath('places.0.name', 'Test Restaurant');
    }

    public function test_nearby_endpoint_rejects_invalid_type(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create([
            'latitude' => 23.7808875,
            'longitude' => 90.2792371,
        ]);

        $response = $this->actingAs($user)->getJson(
            route('maps.nearby', $hotel) . '?type=not_a_real_type'
        );

        $response->assertStatus(422);
    }

    public function test_distance_endpoint_returns_distance_and_duration(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/distancematrix/*' => Http::response([
                'rows' => [[
                    'elements' => [[
                        'status' => 'OK',
                        'distance' => ['text' => '4.2 km'],
                        'duration' => ['text' => '12 mins'],
                    ]],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        $hotel = Hotel::factory()->create([
            'latitude' => 23.7808875,
            'longitude' => 90.2792371,
        ]);

        $response = $this->actingAs($user)->getJson(
            route('maps.distance', $hotel) . '?destination_lat=23.81&destination_lng=90.41&mode=driving'
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'distance' => '4.2 km',
            'duration' => '12 mins',
        ]);
    }
}
