<?php

use Illuminate\Database\Migrations\Migration;

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