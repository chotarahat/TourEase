<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Wishlist Model
 * Owner: MD. Neamatullah Rahat
 * Feature: Wishlist & Saved Trips (Module 3)
 */
class Wishlist extends Model
{
    protected $collection = 'wishlists';

    protected $fillable = [
        'traveler_id',
        'hotel_id',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function traveler()
    {
        return $this->belongsTo(User::class, 'traveler_id');
    }
}
