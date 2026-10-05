@php($maptilerApiKey = config('services.maptiler.key'))

@if ($maptilerApiKey)
    @once
        @push('styles')
            <link rel="stylesheet" href="https://cdn.maptiler.com/maptiler-sdk-js/v4.1.0/maptiler-sdk.css">
            <style>#branch-location-map { height: 340px; border-radius: .35rem; }</style>
        @endpush
        @push('scripts')
            <script src="https://cdn.maptiler.com/maptiler-sdk-js/v4.1.0/maptiler-sdk.umd.min.js"></script>
        @endpush
    @endonce

    <div class="form-group">
        <label>@lang('gps_inventory.branches.map_picker')</label>
        <div id="branch-location-map" data-api-key="{{ $maptilerApiKey }}"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="use-current-location">@lang('gps_inventory.branches.use_current_location')</button>
        <small id="branch-location-status" class="form-text text-muted">@lang('gps_inventory.branches.map_picker_help')</small>
    </div>

    @push('scripts')
        <script>
            (function () {
                const mapElement = document.getElementById('branch-location-map');

                if (!mapElement || !window.maptilersdk) {
                    return;
                }

                const latitudeInput = document.querySelector('input[name="latitude"]');
                const longitudeInput = document.querySelector('input[name="longitude"]');
                const status = document.getElementById('branch-location-status');
                const defaultCenter = [31.2357, 30.0444];
                const initialLatitude = Number(latitudeInput.value);
                const initialLongitude = Number(longitudeInput.value);
                const hasCoordinates = Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);

                maptilersdk.config.apiKey = mapElement.dataset.apiKey;
                const map = new maptilersdk.Map({
                    container: mapElement,
                    style: maptilersdk.MapStyle.STREETS,
                    center: hasCoordinates ? [initialLongitude, initialLatitude] : defaultCenter,
                    zoom: hasCoordinates ? 15 : 10,
                    navigationControl: true,
                    geolocateControl: true,
                    scaleControl: true,
                });
                const marker = new maptilersdk.Marker({ draggable: true })
                    .setLngLat(hasCoordinates ? [initialLongitude, initialLatitude] : defaultCenter)
                    .addTo(map);

                const setCoordinates = (longitude, latitude, message) => {
                    longitudeInput.value = Number(longitude).toFixed(7);
                    latitudeInput.value = Number(latitude).toFixed(7);
                    marker.setLngLat([longitude, latitude]);
                    map.flyTo({ center: [longitude, latitude], zoom: 16 });
                    status.textContent = message || '{{ __('gps_inventory.branches.coordinates_updated') }}';
                };

                map.on('click', (event) => setCoordinates(event.lngLat.lng, event.lngLat.lat));
                marker.on('dragend', () => {
                    const position = marker.getLngLat();
                    setCoordinates(position.lng, position.lat);
                });
                latitudeInput.addEventListener('change', () => {
                    if (latitudeInput.value !== '' && longitudeInput.value !== '') {
                        setCoordinates(longitudeInput.value, latitudeInput.value);
                    }
                });
                longitudeInput.addEventListener('change', () => {
                    if (latitudeInput.value !== '' && longitudeInput.value !== '') {
                        setCoordinates(longitudeInput.value, latitudeInput.value);
                    }
                });
                document.getElementById('use-current-location').addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        status.textContent = '{{ __('gps_inventory.branches.location_not_supported') }}';
                        return;
                    }

                    status.textContent = '{{ __('gps_inventory.branches.detecting_location') }}';
                    navigator.geolocation.getCurrentPosition(
                        (position) => setCoordinates(position.coords.longitude, position.coords.latitude, '{{ __('gps_inventory.branches.coordinates_updated') }}'),
                        () => status.textContent = '{{ __('gps_inventory.branches.location_unavailable') }}',
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                    );
                });
            })();
        </script>
    @endpush
@else
    <div class="alert alert-warning small">
        @lang('gps_inventory.branches.map_key_missing')
    </div>
@endif
