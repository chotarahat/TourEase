{{--
    Partial: reviews/partials/_review-card.blade.php
    Owner: MD. Neamatullah Rahat
    Expects: $review (Review model instance, with ->traveler relationship)
--}}

<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-sm">
                {{ strtoupper(substr($review->traveler->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <p class="font-medium text-gray-800 text-sm">
                    {{ $review->traveler->name ?? 'Anonymous Traveler' }}
                </p>
                <p class="text-xs text-gray-400">
                    {{ $review->created_at?->diffForHumans() }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-0.5" aria-label="{{ $review->rating }} out of 5 stars">
            @for ($i = 1; $i <= 5; $i++)
                <span class="{{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
            @endfor
        </div>
    </div>

    <p class="text-gray-700 text-sm mt-3 leading-relaxed">
        {{ $review->review }}
    </p>

    @if (!empty($review->images))
        <div class="flex gap-2 mt-3 overflow-x-auto">
            @foreach ($review->images as $imagePath)
                <img
                    src="{{ asset('uploads/reviews/' . $imagePath) }}"
                    alt="Travel photo"
                    class="w-20 h-20 object-cover rounded-lg border border-gray-100 flex-shrink-0"
                >
            @endforeach
        </div>
    @endif

    @auth
        @if (auth()->id() === $review->traveler_id)
            <div class="flex gap-3 mt-3 pt-3 border-t border-gray-100">
                <a href="{{ route('reviews.edit', [$review->hotel_id, $review->id]) }}"
                   class="text-xs text-blue-600 hover:underline">Edit</a>

                <form action="{{ route('reviews.destroy', [$review->hotel_id, $review->id]) }}"
                      method="POST"
                      onsubmit="return confirm('Delete this review? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                </form>
            </div>
        @endif
    @endauth
</div>