<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
        $collection = app('db')->connection('mongodb')->getCollection('hotels');

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