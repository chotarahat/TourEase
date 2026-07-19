/**
 * reviews.js
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 */

document.addEventListener('DOMContentLoaded', () => {
    initStarPicker();
    initPhotoPreview();
});

function initStarPicker() {
    const starContainer = document.getElementById('star-picker');
    if (!starContainer) return;

    const stars = starContainer.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating-input');

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

function paintStars(stars, value) {
    stars.forEach((star) => {
        const starValue = parseInt(star.dataset.value, 10);
        star.classList.toggle('text-yellow-400', starValue <= value);
        star.classList.toggle('text-gray-200', starValue > value);
    });
}

function initPhotoPreview() {
    const photoInput = document.getElementById('photos-input');
    const previewContainer = document.getElementById('photo-preview');
    if (!photoInput) return;

    let currentFiles = [];

    photoInput.addEventListener('change', () => {
        const newFiles = Array.from(photoInput.files);

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

    function syncFileInput() {
        const dataTransfer = new DataTransfer();
        currentFiles.forEach((file) => dataTransfer.items.add(file));
        photoInput.files = dataTransfer.files;
    }
}