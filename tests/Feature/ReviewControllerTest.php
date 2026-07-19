<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ReviewControllerTest
 * Owner: MD. Neamatullah Rahat
 *
 * Covers the business rules unique to this feature: completed-booking
 * eligibility, one-review-per-hotel limit, ownership authorization,
 * and photo upload validation.
 */
class ReviewControllerTest extends TestCase
{
    /**
     * A traveler WITHOUT a completed booking cannot access the review form.
     */
    public function test_cannot_access_create_form_without_completed_booking(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);
        $hotel = Hotel::factory()->create();

        $response = $this->actingAs($traveler)->get(route('reviews.create', $hotel));

        $response->assertRedirect(route('reviews.index', $hotel));
        $response->assertSessionHas('error');
    }

    /**
     * A traveler WITH a completed booking CAN access and submit the form.
     */
    public function test_can_submit_review_with_completed_booking(): void
    {
        Storage::fake('public');

        $traveler = User::factory()->create(['role' => 'traveler']);
        $hotel = Hotel::factory()->create();

        Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'hotel_id' => $hotel->id,
            'booking_status' => 'Completed',
        ]);

        $response = $this->actingAs($traveler)->post(route('reviews.store', $hotel), [
            'rating' => 5,
            'review' => 'Absolutely wonderful stay, highly recommended!',
            'photos' => [
                UploadedFile::fake()->image('trip1.jpg'),
            ],
        ]);

        $response->assertRedirect(route('reviews.index', $hotel));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'hotel_id' => $hotel->id,
            'traveler_id' => $traveler->id,
            'rating' => 5,
        ]);
    }

    /**
     * A traveler cannot submit a second review for the same hotel.
     */
    public function test_cannot_submit_duplicate_review_for_same_hotel(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);
        $hotel = Hotel::factory()->create();

        Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'hotel_id' => $hotel->id,
            'booking_status' => 'Completed',
        ]);

        Review::factory()->create([
            'hotel_id' => $hotel->id,
            'traveler_id' => $traveler->id,
        ]);

        $response = $this->actingAs($traveler)->get(route('reviews.create', $hotel));

        $response->assertRedirect(route('reviews.index', $hotel));
        $response->assertSessionHas('error', 'You have already reviewed this hotel.');
    }

    /**
     * Validation should reject a rating outside 1-5 and short review text.
     */
    public function test_validation_rejects_invalid_rating_and_short_text(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);
        $hotel = Hotel::factory()->create();

        Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'hotel_id' => $hotel->id,
            'booking_status' => 'Completed',
        ]);

        $response = $this->actingAs($traveler)->post(route('reviews.store', $hotel), [
            'rating' => 7, // invalid — max is 5
            'review' => 'Too short', // under 10 chars
        ]);

        $response->assertSessionHasErrors(['rating', 'review']);
    }

    /**
     * A traveler cannot edit or delete another traveler's review.
     */
    public function test_cannot_edit_or_delete_another_users_review(): void
    {
        $owner = User::factory()->create(['role' => 'traveler']);
        $intruder = User::factory()->create(['role' => 'traveler']);
        $hotel = Hotel::factory()->create();

        $review = Review::factory()->create([
            'hotel_id' => $hotel->id,
            'traveler_id' => $owner->id,
        ]);

        $editResponse = $this->actingAs($intruder)->get(route('reviews.edit', [$hotel, $review]));
        $editResponse->assertSessionHas('error', 'You are not authorized to edit this review.');

        $deleteResponse = $this->actingAs($intruder)->delete(route('reviews.destroy', [$hotel, $review]));
        $deleteResponse->assertSessionHas('error', 'You are not authorized to delete this review.');

        $this->assertDatabaseHas('reviews', ['_id' => $review->id]);
    }

    /**
     * An Administrator CAN delete any review, regardless of ownership.
     */
    public function test_admin_can_delete_any_review(): void
    {
        $owner = User::factory()->create(['role' => 'traveler']);
        $admin = User::factory()->create(['role' => 'admin']);
        $hotel = Hotel::factory()->create();

        $review = Review::factory()->create([
            'hotel_id' => $hotel->id,
            'traveler_id' => $owner->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('reviews.destroy', [$hotel, $review]));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('reviews', ['_id' => $review->id]);
    }

    /**
     * Uploading more than 5 photos should fail server-side validation,
     * even if a user bypasses the client-side JS limit.
     */
    public function test_rejects_more_than_five_photos(): void
    {
        Storage::fake('public');

        $traveler = User::factory()->create(['role' => 'traveler']);
        $hotel = Hotel::factory()->create();

        Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'hotel_id' => $hotel->id,
            'booking_status' => 'Completed',
        ]);

        $photos = collect(range(1, 6))->map(
            fn ($i) => UploadedFile::fake()->image("trip{$i}.jpg")
        )->all();

        $response = $this->actingAs($traveler)->post(route('reviews.store', $hotel), [
            'rating' => 4,
            'review' => 'A perfectly fine stay overall, would visit again.',
            'photos' => $photos,
        ]);

        $response->assertSessionHasErrors('photos');
    }

    /**
     * Hotel::averageRating() should correctly compute the mean of
     * all reviews for that hotel.
     */
    public function test_hotel_average_rating_is_computed_correctly(): void
    {
        $hotel = Hotel::factory()->create();

        Review::factory()->create(['hotel_id' => $hotel->id, 'rating' => 5]);
        Review::factory()->create(['hotel_id' => $hotel->id, 'rating' => 3]);

        $this->assertEquals(4.0, $hotel->fresh()->averageRating());
    }
}