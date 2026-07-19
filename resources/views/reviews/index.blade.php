{{--
    View: reviews/index.blade.php
    Owner: MD. Neamatullah Rahat

    Purpose: Lists all reviews for a hotel with an average rating summary
    at the top and a "Write a Review" call-to-action.

    Data expected from ReviewController@index:
        $hotel          -> Hotel model
        $reviews        -> Paginated Review collection
        $averageRating  -> float|null
--}}

@extends('layouts.app')

@section('title', $hotel->hotel_name . ' - Reviews')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Rating summary header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ $hotel->hotel_name }} — Reviews</h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-2xl font-bold text-gray-800">
                    {{ $averageRating ?? 'No ratings yet' }}
                </span>
                @if ($averageRating)
                    <div class="flex text-yellow-400">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= round($averageRating) ? '' : 'text-gray-200' }}">★</span>
                        @endfor
                    </div>
                    <span class="text-sm text-gray-400">({{ $reviews->total() }} reviews)</span>
                @endif
            </div>
        </div>

        <a href="{{ route('reviews.create', $hotel) }}"
           class="inline-flex justify-center items-center px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
            ✍️ Write a Review
        </a>
    </div>

    {{-- Review list --}}
    <div class="space-y-4">
        @forelse ($reviews as $review)
            @include('reviews.partials._review-card', ['review' => $review])
        @empty
            <div class="text-center py-16 bg-white rounded-xl border border-dashed border-gray-200">
                <p class="text-gray-400">No reviews yet. Be the first to share your experience!</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $reviews->links() }}
    </div>

</div>
@endsection