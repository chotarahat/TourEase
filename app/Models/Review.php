<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Review Model
 *
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 *
 * Represents a single traveler review for a hotel, stored in the
 * `reviews` collection. Each review is scoped to one hotel and one
 * traveler (enforced at the application level in ReviewController,
 * since MongoDB has no foreign key constraints).
 */
class Review extends Model
{
    /**
     * MongoDB collection name — explicit rather than relying on
     * Laravel's pluralization guess, for clarity during viva.
     */
    protected $collection = 'reviews';

    protected $fillable = [
        'hotel_id',
        'traveler_id',
        'rating',
        'review',
        'images',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
    ];

    /**
     * The hotel this review belongs to.
     * Read-only relationship to Arijit's Hotel model — used for
     * display purposes only (e.g. showing hotel name if reviews
     * are ever listed outside a hotel-scoped page).
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * The traveler who wrote this review.
     * Read-only relationship to the shared User model.
     */
    public function traveler()
    {
        return $this->belongsTo(User::class, 'traveler_id');
    }
}