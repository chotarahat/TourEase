{{--
    View: reviews/edit.blade.php
    Owner: MD. Neamatullah Rahat

    Purpose: Edit form for an existing review. Nearly identical to
    create.blade.php, but pre-fills existing values and shows currently
    uploaded photos with an option to keep or replace them.

    Data expected from ReviewController@edit:
        $hotel  -> Hotel model
        $review -> Review model (must belong to the authenticated user —
                   enforced in controller before this view ever renders)
--}}

@extends('layouts.app')

@section('title', 'Edit Review - ' . $hotel->hotel_name)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">

    <h1 class="text-xl font-bold text-gray-800 mb-1">Edit your review</h1>
    <p class="text-gray-500 text-sm mb-6">{{ $hotel->hotel_name }}</p>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('reviews.update', [$hotel, $review]) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        @method('PUT')

        {{-- Interactive star rating picker — same widget as create.blade.php,
             pre-filled with the review's current rating via old()/value fallback --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
            <div id="star-picker" class="flex gap-1 text-3xl cursor-pointer">
                @for ($i = 1; $i <= 5; $i++)
                    <span class="star text-gray-200 transition" data-value="{{ $i }}">★</span>
                @endfor
            </div>
            <input type="hidden" name="rating" id="rating-input" value="{{ old('rating', $review->rating) }}">
        </div>

        <div>
            <label for="review" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
            <textarea
                name="review"
                id="review"
                rows="5"
                maxlength="2000"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >{{ old('review', $review->review) }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Minimum 10 characters.</p>
        </div>

        {{-- Currently uploaded photos — shown separately from new uploads,
             since submitting new photos REPLACES these (per ReviewController@update logic) --}}
        @if (!empty($review->images))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Photos</label>
                <div class="flex gap-2 flex-wrap">
                    @foreach ($review->images as $imagePath)
                        <img
                            src="{{ asset('uploads/reviews/' . $imagePath) }}"
                            alt="Current travel photo"
                            class="w-20 h-20 object-cover rounded-lg border border-gray-200"
                        >
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    Uploading new photos below will replace these.
                </p>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Replace Photos (optional, up to 5)</label>
            <input
                type="file"
                name="photos[]"
                id="photos-input"
                accept="image/png, image/jpeg, image/webp"
                multiple
                class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >
            <div id="photo-preview" class="flex gap-2 mt-3 flex-wrap"></div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                Update Review
            </button>
            <a href="{{ route('reviews.index', $hotel) }}" class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium text-gray-600">
                Cancel
            </a>
        </div>
    </form>
</div>

<script src="{{ asset('js/reviews.js') }}"></script>
@endsection