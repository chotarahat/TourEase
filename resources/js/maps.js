/**
 * maps.js
 * Owner: MD. Neamatullah Rahat
 * Feature: Interactive Maps & Nearby Attractions
 */

let map;
let hotelMarker;
let nearbyMarkers = [];
let distanceMarker;

function initHotelMap() {
    const mapEl = document.getElementById('hotel-map');
    const lat = parseFloat(mapEl.dataset.hotelLat);
    const lng = parseFloat(mapEl.dataset.hotelLng);
    const hotelName = mapEl.dataset.hotelName;

    document.getElementById('map-loading-text')?.remove();

    map = new google.maps.Map(mapEl, {
        center: { lat, lng },
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: true,
        fullscreenControl: true,
    });

    hotelMarker = new google.maps.Marker({
        position: { lat, lng },
        map,
        title: hotelName,
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' },
    });

    const hotelInfoWindow = new google.maps.InfoWindow({
        content: `<strong>${hotelName}</strong><br>Your selected hotel`,
    });
    hotelMarker.addListener('click', () => hotelInfoWindow.open(map, hotelMarker));

    map.addListener('click', (event) => {
        handleMapClickForDistance(event.latLng.lat(), event.latLng.lng());
    });

    initNearbyFilterButtons(lat, lng);
}

function initNearbyFilterButtons() {
    const buttons = document.querySelectorAll('.nearby-filter-btn');

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            buttons.forEach((b) => b.classList.remove('bg-blue-100', 'border-blue-400'));
            btn.classList.add('bg-blue-100', 'border-blue-400');

            const type = btn.dataset.type;
            fetchNearbyPlaces(type);
        });
    });
}

async function fetchNearbyPlaces(type) {
    const resultsContainer = document.getElementById('nearby-results');
    resultsContainer.innerHTML = `<p class="text-sm text-gray-400 text-center py-6">Searching...</p>`;

    clearNearbyMarkers();

    try {
        const url = `${window.TourEaseMapsConfig.nearbyUrl}?type=${encodeURIComponent(type)}`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });

        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
        }

        const data = await response.json();

        if (!data.success || data.places.length === 0) {
            resultsContainer.innerHTML = `<p class="text-sm text-gray-400 text-center py-6">No places found nearby.</p>`;
            return;
        }

        renderNearbyResults(data.places);
    } catch (error) {
        console.error('Failed to fetch nearby places:', error);
        resultsContainer.innerHTML = `<p class="text-sm text-red-400 text-center py-6">Could not load nearby places. Please try again.</p>`;
    }
}

function renderNearbyResults(places) {
    const resultsContainer = document.getElementById('nearby-results');
    resultsContainer.innerHTML = '';

    places.forEach((place, index) => {
        if (place.lat && place.lng) {
            const marker = new google.maps.Marker({
                position: { lat: place.lat, lng: place.lng },
                map,
                title: place.name,
                icon: { url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' },
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `<strong>${place.name}</strong><br>${place.address}${place.rating ? `<br>⭐ ${place.rating}` : ''}`,
            });
            marker.addListener('click', () => infoWindow.open(map, marker));
            nearbyMarkers.push(marker);
        }

        const item = document.createElement('div');
        item.className = 'p-2 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition';
        item.innerHTML = `
            <p class="text-sm font-medium text-gray-800">${place.name}</p>
            <p class="text-xs text-gray-500">${place.address}</p>
            ${place.rating ? `<p class="text-xs text-yellow-600 mt-0.5">⭐ ${place.rating}</p>` : ''}
        `;

        item.addEventListener('click', () => {
            if (place.lat && place.lng) {
                map.panTo({ lat: place.lat, lng: place.lng });
                map.setZoom(17);
                google.maps.event.trigger(nearbyMarkers[index], 'click');
            }
        });

        resultsContainer.appendChild(item);
    });
}

function clearNearbyMarkers() {
    nearbyMarkers.forEach((marker) => marker.setMap(null));
    nearbyMarkers = [];
}

async function handleMapClickForDistance(destLat, destLng) {
    if (distanceMarker) {
        distanceMarker.setMap(null);
    }

    distanceMarker = new google.maps.Marker({
        position: { lat: destLat, lng: destLng },
        map,
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' },
    });

    const resultBox = document.getElementById('distance-result');
    const distanceValue = document.getElementById('distance-value');
    const durationValue = document.getElementById('duration-value');

    try {
        const url = `${window.TourEaseMapsConfig.distanceUrl}?destination_lat=${destLat}&destination_lng=${destLng}&mode=driving`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });

        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
        }

        const data = await response.json();

        distanceValue.textContent = data.distance;
        durationValue.textContent = data.duration;
        resultBox.classList.remove('hidden');
    } catch (error) {
        console.error('Failed to calculate distance:', error);
        distanceValue.textContent = 'N/A';
        durationValue.textContent = 'N/A';
        resultBox.classList.remove('hidden');
    }
}
