/**
 * reviews.js
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 *
 * Responsibility: Powers two pieces of interactivity on the review
 * submission form (reviews/create.blade.php):
 *   1. Interactive 5-star rating picker (click to select, hover to preview)
 *   2. Multi-photo upload with client-side thumbnail preview + removal
 *
 * No AJAX here — the form itself does a normal POST submit
 * (server-rendered redirect on success/failure), since a full-page
 * reload after review submission is expected UX for this kind of form.
 */

document.addEventListener('DOMContentLoaded', () => {
    initStarPicker();
    initPhotoPreview();
});

/**
 * Wires up the star rating picker.
 * - Hovering highlights stars up to the hovered one (preview only)
 * - Clicking sets the actual rating and updates the hidden input
 * - Leaving the picker area reverts to showing the currently SELECTED rating,
 *   not just clearing back to zero (important UX detail — otherwise the
 *   stars look empty right after you pick one, which is confusing)
 */
function initStarPicker() {
    const starContainer = document.getElementById('star-picker');
    if (!starContainer) return; // Guard: only runs on create/edit pages

    const stars = starContainer.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating-input');

    // Restore a previously selected rating (e.g. after a validation error
    // reloads the page with old('rating') still set)
    let selectedRating = parseInt(ratingInput.value, 10) || 0;
    paintStars(stars, selectedRating);

    stars.forEach((star) => {
        const value = parseInt(star.dataset.value, 10);

        star.addEventListener('mouseenter', () => paintStars(stars, value));

        star.addEventListener('click', () => {
            selectedRating = value;
            ratingInput.value = value;
            paintStars(stars, value);
        });
    });

    starContainer.addEventListener('mouseleave', () => {
        paintStars(stars, selectedRating);
    });
}

/**
 * Colors stars 1..value as filled (yellow), rest as empty (gray).
 * Shared by hover-preview and click-select so both use identical logic.
 */
function paintStars(stars, value) {
    stars.forEach((star) => {
        const starValue = parseInt(star.dataset.value, 10);
        star.classList.toggle('text-yellow-400', starValue <= value);
        star.classList.toggle('text-gray-200', starValue > value);
    });
}

/**
 * Wires up the photo input to show thumbnail previews of selected
 * files before upload, with a remove (×) button per thumbnail.
 *
 * Note: removing a preview thumbnail also removes that file from the
 * actual FileList submitted, using the DataTransfer API — a real file
 * input's FileList is read-only, so we rebuild it manually.
 */
function initPhotoPreview() {
    const photoInput = document.getElementById('photos-input');
    const previewContainer = document.getElementById('photo-preview');
    if (!photoInput) return; // Guard: only runs on create/edit pages

    // Holds the currently "active" set of files, since we allow removal
    let currentFiles = [];

    photoInput.addEventListener('change', () => {
        const newFiles = Array.from(photoInput.files);

        // Enforce max 5 photos client-side (server also enforces this,
        // this is just faster feedback for the user)
        if (newFiles.length > 5) {
            alert('You can upload a maximum of 5 photos.');
            photoInput.value = '';
            return;
        }

        currentFiles = newFiles;
        renderPreviews();
    });

    function renderPreviews() {
        previewContainer.innerHTML = '';

        currentFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = (event) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'relative';
                wrapper.innerHTML = `
                    <img src="${event.target.result}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                    <button type="button" data-index="${index}"
                        class="remove-photo-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs leading-none">
                        ×
                    </button>
                `;
                previewContainer.appendChild(wrapper);

                wrapper.querySelector('.remove-photo-btn').addEventListener('click', () => {
                    currentFiles.splice(index, 1);
                    syncFileInput();
                    renderPreviews();
                });
            };

            reader.readAsDataURL(file);
        });
    }

    // Rebuilds the actual <input type="file"> FileList from currentFiles,
    // since browsers don't allow directly mutating a FileList.
    function syncFileInput() {
        const dataTransfer = new DataTransfer();
        currentFiles.forEach((file) => dataTransfer.items.add(file));
        photoInput.files = dataTransfer.files;
    }
}