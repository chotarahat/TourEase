<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Creates the `reviews` collection in MongoDB.
 *
 * Owner: MD. Neamatullah Rahat
 *
 * MongoDB doesn't require a schema definition to store documents, but
 * this migration creates useful indexes:
 *   1. Compound index on (hotel_id, traveler_id) — speeds up the
 *      "has this traveler already reviewed this hotel?" check in
 *      ReviewController@create, and enforces it stays fast as the
 *      collection grows.
 *   2. Index on hotel_id alone — speeds up ReviewController@index's
 *      "all reviews for this hotel" query and Hotel::averageRating().
 */
return new class extends Migration
{
    public function up(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('reviews');

        $collection->createIndex(
            ['hotel_id' => 1, 'traveler_id' => 1],
            ['name' => 'hotel_traveler_index']
        );

        $collection->createIndex(
            ['hotel_id' => 1],
            ['name' => 'hotel_id_index']
        );
    }

    public function down(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('reviews');
        $collection->dropIndex('hotel_traveler_index');
        $collection->dropIndex('hotel_id_index');
    }
};