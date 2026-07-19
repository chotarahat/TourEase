<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('wishlists');

        $collection->createIndex(
            ['traveler_id' => 1, 'hotel_id' => 1],
            ['unique' => true, 'name' => 'unique_traveler_hotel_wishlist']
        );
    }

    public function down(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('wishlists');
        $collection->dropIndex('unique_traveler_hotel_wishlist');
    }
};
