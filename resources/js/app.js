import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2xUrl,
    iconUrl: markerIconUrl,
    shadowUrl: markerShadowUrl,
});

const DEFAULT_CENTER = [14.5995, 120.9842];
const DEFAULT_ZOOM = 12;
const PIN_ZOOM = 16;
const mapRegistry = new WeakMap();
const pickerSyncRegistry = new WeakMap();

const toNumber = (value) => {
    const parsed = Number.parseFloat(`${value ?? ''}`);
    return Number.isFinite(parsed) ? parsed : null;
};

const formatCoordinate = (value) => value.toFixed(7);
const isValidLatitude = (value) => value >= -90 && value <= 90;
const isValidLongitude = (value) => value >= -180 && value <= 180;

const addOsmTiles = (map) => {
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);
};

const setInputValue = (root, selector, value) => {
    const input = root.querySelector(selector);
    if (input instanceof HTMLInputElement) {
        input.value = value;
    }
};

const updateLivewireCoordinates = (componentId, latitude, longitude) => {
    if (!componentId || !window.Livewire || typeof window.Livewire.find !== 'function') {
        return;
    }

    const component = window.Livewire.find(componentId);
    if (!component) {
        return;
    }

    component.set('latitude', latitude);
    component.set('longitude', longitude);
};

const initializeLeafletPicker = (root) => {
    const mapElement = root.querySelector('[data-leaflet-picker-map]');
    if (!(mapElement instanceof HTMLElement) || mapRegistry.has(mapElement)) {
        return;
    }

    const componentId = root.dataset.livewireId ?? '';
    let latitude = toNumber(root.dataset.lat);
    let longitude = toNumber(root.dataset.lng);

    const hasInitialPin = latitude !== null && longitude !== null;
    const map = L.map(mapElement, { scrollWheelZoom: false }).setView(
        hasInitialPin ? [latitude, longitude] : DEFAULT_CENTER,
        hasInitialPin ? PIN_ZOOM : DEFAULT_ZOOM
    );
    addOsmTiles(map);

    let marker = null;
    if (hasInitialPin) {
        marker = L.marker([latitude, longitude]).addTo(map);
    }

    const syncCoordinates = (nextLatitude, nextLongitude, shouldCenter = true) => {
        latitude = nextLatitude;
        longitude = nextLongitude;

        if (latitude !== null && longitude !== null) {
            const latText = formatCoordinate(latitude);
            const lngText = formatCoordinate(longitude);

            if (!marker) {
                marker = L.marker([latitude, longitude]).addTo(map);
            } else {
                marker.setLatLng([latitude, longitude]);
            }

            if (shouldCenter) {
                map.setView([latitude, longitude], PIN_ZOOM);
            }

            setInputValue(root, '[data-leaflet-lat-display]', latText);
            setInputValue(root, '[data-leaflet-lng-display]', lngText);
            updateLivewireCoordinates(componentId, latText, lngText);
            return;
        }

        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }

        map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        setInputValue(root, '[data-leaflet-lat-display]', '');
        setInputValue(root, '[data-leaflet-lng-display]', '');
        updateLivewireCoordinates(componentId, null, null);
    };

    const latitudeInput = root.querySelector('[data-leaflet-lat-display]');
    const longitudeInput = root.querySelector('[data-leaflet-lng-display]');
    const syncFromManualInput = () => {
        if (!(latitudeInput instanceof HTMLInputElement) || !(longitudeInput instanceof HTMLInputElement)) {
            return;
        }

        const latitudeRaw = latitudeInput.value.trim();
        const longitudeRaw = longitudeInput.value.trim();

        if (latitudeRaw === '' && longitudeRaw === '') {
            syncCoordinates(null, null, false);
            return;
        }

        const typedLatitude = toNumber(latitudeRaw);
        const typedLongitude = toNumber(longitudeRaw);

        if (typedLatitude === null || typedLongitude === null) {
            return;
        }

        if (!isValidLatitude(typedLatitude) || !isValidLongitude(typedLongitude)) {
            return;
        }

        syncCoordinates(typedLatitude, typedLongitude, true);
    };

    if (latitudeInput instanceof HTMLInputElement) {
        latitudeInput.addEventListener('change', syncFromManualInput);
        latitudeInput.addEventListener('blur', syncFromManualInput);
    }

    if (longitudeInput instanceof HTMLInputElement) {
        longitudeInput.addEventListener('change', syncFromManualInput);
        longitudeInput.addEventListener('blur', syncFromManualInput);
    }

    map.on('click', (event) => {
        syncCoordinates(event.latlng.lat, event.latlng.lng, true);
    });

    const geolocateButton = root.querySelector('[data-leaflet-action="geolocate"]');
    if (geolocateButton instanceof HTMLButtonElement) {
        geolocateButton.addEventListener('click', () => {
            if (!navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    syncCoordinates(position.coords.latitude, position.coords.longitude, true);
                },
                () => {},
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    const clearButton = root.querySelector('[data-leaflet-action="clear"]');
    if (clearButton instanceof HTMLButtonElement) {
        clearButton.addEventListener('click', () => {
            syncCoordinates(null, null, false);
        });
    }

    mapRegistry.set(mapElement, map);
    pickerSyncRegistry.set(mapElement, { syncCoordinates });
    syncFromManualInput();
    window.setTimeout(() => map.invalidateSize(), 0);
};

const initializeLeafletReadonly = (mapElement) => {
    if (!(mapElement instanceof HTMLElement) || mapRegistry.has(mapElement)) {
        return;
    }

    const latitude = toNumber(mapElement.dataset.lat);
    const longitude = toNumber(mapElement.dataset.lng);

    if (latitude === null || longitude === null) {
        return;
    }

    const map = L.map(mapElement, {
        scrollWheelZoom: false,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true,
        boxZoom: false,
        keyboard: false,
    }).setView([latitude, longitude], PIN_ZOOM);

    addOsmTiles(map);
    L.marker([latitude, longitude]).addTo(map);

    mapRegistry.set(mapElement, map);
    window.setTimeout(() => map.invalidateSize(), 0);
};

const bootstrapLeafletMaps = () => {
    document.querySelectorAll('[data-leaflet-picker]').forEach((root) => {
        initializeLeafletPicker(root);
    });

    document.querySelectorAll('[data-leaflet-readonly]').forEach((element) => {
        initializeLeafletReadonly(element);
    });
};

const handleLeafletPresetCoordinates = (event) => {
    const detail = event.detail ?? {};
    const latitude = toNumber(detail.latitude);
    const longitude = toNumber(detail.longitude);
    const componentId = `${detail.componentId ?? ''}`;

    if (latitude === null || longitude === null) {
        return;
    }

    if (!isValidLatitude(latitude) || !isValidLongitude(longitude)) {
        return;
    }

    document.querySelectorAll('[data-leaflet-picker]').forEach((root) => {
        if (componentId !== '' && root.dataset.livewireId !== componentId) {
            return;
        }

        const mapElement = root.querySelector('[data-leaflet-picker-map]');
        if (!(mapElement instanceof HTMLElement)) {
            return;
        }

        const syncState = pickerSyncRegistry.get(mapElement);
        if (!syncState || typeof syncState.syncCoordinates !== 'function') {
            return;
        }

        syncState.syncCoordinates(latitude, longitude, true);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapLeafletMaps, { once: true });
} else {
    bootstrapLeafletMaps();
}

document.addEventListener('livewire:navigated', bootstrapLeafletMaps);
document.addEventListener('leaflet-picker-set-coordinates', handleLeafletPresetCoordinates);
