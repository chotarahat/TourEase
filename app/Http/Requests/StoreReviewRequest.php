<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreReviewRequest
 *
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 *
 * Responsibility: Validates review submission/update data before it ever
 * reaches ReviewController. Using a Form Request (rather than inline
 * $request->validate()) keeps validation rules reusable between store()
 * and update(), and keeps the controller free of validation clutter —
 * a Laravel best practice for anything beyond 2-3 simple rules.
 */
class StoreReviewRequest extends FormRequest
{
    /**
     * Authorization is handled explicitly inside ReviewController
     * (ownership + completed-booking checks), so this just allows
     * any authenticated user through to those checks.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB per photo
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Please select a star rating.',
            'rating.min' => 'Rating must be between 1 and 5 stars.',
            'review.required' => 'Please write a review before submitting.',
            'review.min' => 'Your review should be at least 10 characters.',
            'photos.max' => 'You can upload a maximum of 5 photos.',
            'photos.*.image' => 'Each file must be a valid image.',
            'photos.*.max' => 'Each photo must be smaller than 5MB.',
        ];
    }
}