<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Hotel Model
 *
 * NOTE: This is Arijit's model (Hotel Listing Management, Module 1 owner).
 * This file is included here ONLY to show the exact additions Rahat made
 * for his own features (Maps + Reviews). When merging into the real
 * project, do NOT overwrite Arijit's full Hotel.php with this file —
 * instead, manually add the highlighted methods/fields below into his
 * existing model.
 *
 * === Added by MD. Neamatullah Rahat for Feature 1 (Maps) ===
 *   - latitude, longitude fields (+ casts)
 *   - location_point auto-sync via booted() hook (GeoJSON for 2dsphere index)
 *   - hasCoordinates() helper
 *
 * === Added by MD. Neamatullah Rahat for Feature 2 (Review & Rating) ===
 *   - averageRating() computed accessor
 *   - reviewCount() computed accessor
 */
class Hotel extends Model
{
    protected $collection = 'hotels';

    protected $fillable = [
        // ...Arijit's existing fields (hotel_name, description, location,
        // images, amenities, policies, owner_id, etc.)

        // --- Added by Rahat (Feature 1: Maps) ---
        'latitude',
        'longitude',
    ];

    protected $casts = [
        // ...Arijit's existing casts

        // --- Added by Rahat (Feature 1: Maps) ---
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * --- Added by Rahat (Feature 1: Maps) ---
     * Auto-syncs a GeoJSON `location_point` field whenever latitude/longitude
     * are set, so the 2dsphere geospatial index (see migration) stays usable.
     */
    protected static function booted(): void
    {
        static::saving(function (Hotel $hotel) {
            if ($hotel->latitude && $hotel->longitude) {
                $hotel->location_point = [
                    'type' => 'Point',
                    'coordinates' => [(float) $hotel->longitude, (float) $hotel->latitude],
                ];
            }
        });
    }

    /**
     * --- Added by Rahat (Feature 1: Maps) ---
     * Check whether this hotel has valid map coordinates set.
     */
    public function hasCoordinates(): bool
    {
        return ! is_null($this->latitude) && ! is_null($this->longitude);
    }

    /**
     * --- Added by Rahat (Feature 2: Review & Rating) ---
     * Calculate this hotel's average rating from all its reviews.
     * Computed on-demand (not stored) so it never goes stale.
     */
    public function averageRating(): ?float
    {
        $average = Review::where('hotel_id', $this->id)->avg('rating');

        return $average ? round($average, 1) : null;
    }

    /**
     * --- Added by Rahat (Feature 2: Review & Rating) ---
     * Total number of reviews for this hotel.
     */
    public function reviewCount(): int
    {
        return Review::where('hotel_id', $this->id)->count();
    }
}
