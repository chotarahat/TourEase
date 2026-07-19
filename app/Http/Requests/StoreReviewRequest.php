<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreReviewRequest
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 */
class StoreReviewRequest extends FormRequest
{
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
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
