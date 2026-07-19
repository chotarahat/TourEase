<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Adds geospatial support to the hotels collection for the
 * Interactive Maps & Nearby Attractions feature.
 *
 * Owner: MD. Neamatullah Rahat
 *
 * MongoDB is schemaless, so 'latitude' and 'longitude' fields do not need
 * to be declared ahead of time — they are simply written by HotelController
 * when a hotel manager creates/updates a listing (see integration note
 * below). This migration's real job is creating a 2dsphere index so that
 * geospatial queries (nearby search, distance sorting) run efficiently
 * instead of scanning every document.
 */
return new class extends Migration
{
    public function up(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('hotels');

        // 2dsphere index enables geospatial queries like $near, $geoWithin
        // directly against latitude/longitude fields stored on each hotel.
        $collection->createIndex(
            ['location_point' => '2dsphere']
        );
    }

    public function down(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('hotels');
        $collection->dropIndex('location_point_2dsphere');
    }
};