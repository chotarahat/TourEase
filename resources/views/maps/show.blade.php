{{--
    View: maps/show.blade.php
    Owner: MD. Neamatullah Rahat
    Feature: Interactive Maps & Nearby Attractions

    Data expected from MapsController@show:
        $hotel              -> Hotel model instance (needs ->latitude, ->longitude, ->hotel_name)
        $googleMapsApiKey   -> string, from config('services.google_maps.key')
--}}

@extends('layouts.app')

@section('title', $hotel->hotel_name . ' - Location & Nearby')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
            {{ $hotel->hotel_name }} — Location & Nearby Places
        </h1>
        <p class="text-gray-500 mt-1">
            Explore the surrounding area before you book.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div
                    id="hotel-map"
                    data-hotel-lat="{{ $hotel->latitude }}"
                    data-hotel-lng="{{ $hotel->longitude }}"
                    data-hotel-name="{{ $hotel->hotel_name }}"
                    class="w-full h-[400px] md:h-[500px] bg-gray-100 flex items-center justify-center"
                >
                    <p class="text-gray-400 text-sm" id="map-loading-text">Loading map...</p>
                </div>
            </div>

            <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Estimate Travel Distance</h2>
                <p class="text-sm text-gray-500 mb-3">
                    Click anywhere on the map to estimate distance and travel time from the hotel.
                </p>
                <div id="distance-result" class="hidden bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Distance:</span>
                        <span id="distance-value" class="font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-gray-600">Estimated travel time:</span>
                        <span id="duration-value" class="font-semibold text-gray-800"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sticky top-4">
                <h2 class="font-semibold text-gray-800 mb-3">Nearby</h2>

                <div class="grid grid-cols-2 gap-2 mb-4" id="nearby-filters">
                    <button type="button" data-type="restaurant"
                        class="nearby-filter-btn px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                        🍽️ Restaurants
                    </button>
                    <button type="button" data-type="hospital"
                        class="nearby-filter-btn px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                        🏥 Hospitals
                    </button>
                    <button type="button" data-type="transit_station"
                        class="nearby-filter-btn px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                        🚉 Transport
                    </button>
                    <button type="button" data-type="shopping_mall"
                        class="nearby-filter-btn px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                        🛍️ Shopping
                    </button>
                    <button type="button" data-type="tourist_attraction"
                        class="nearby-filter-btn col-span-2 px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                        📸 Tourist Attractions
                    </button>
                </div>

                <div id="nearby-results" class="space-y-2 max-h-[350px] overflow-y-auto">
                    <p class="text-sm text-gray-400 text-center py-6" id="nearby-empty-state">
                        Select a category above to see nearby places.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.TourEaseMapsConfig = {
        hotelId: "{{ $hotel->id }}",
        nearbyUrl: "{{ route('maps.nearby', $hotel) }}",
        distanceUrl: "{{ route('maps.distance', $hotel) }}",
    };
</script>

<script
    src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initHotelMap"
    async
    defer
></script>

<script src="{{ asset('js/maps.js') }}"></script>

@endsection