@if ($maptilerApiKey)
    <link rel="stylesheet" href="https://cdn.maptiler.com/maptiler-sdk-js/v4.1.0/maptiler-sdk.css">
    <script src="https://cdn.maptiler.com/maptiler-sdk-js/v4.1.0/maptiler-sdk.umd.min.js"></script>
@endif

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.attendance.clock_in')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>

@if ($cannotLogin == false)
<x-form id="clockInForm">
    <div class="modal-body">
            <div class="row justify-content-between">
                <div class="col" id="task_div">
                    @php
                        $shiftNameKey = str($shiftAssigned->shift_name)->camel();
                        $displayShiftName = $shiftAssigned->shift_name === 'Day Off'
                            ? __('modules.attendance.dayOff')
                            : (\Illuminate\Support\Facades\Lang::has('app.' . $shiftNameKey) ? __('app.' . $shiftNameKey) : $shiftAssigned->shift_name);
                    @endphp
                    <h4 class="mb-4 d-flex justify-content-between"><span><i class="fa fa-clock"></i> {{ now()->timezone(company()->timezone)->translatedFormat(company()->date_format . ' ' . company()->time_format) }}</span>
                        <span class="badge badge-info f-14"
                              style="background-color: {{ $shiftAssigned->color }}">{{ $displayShiftName }}</span>
                    </h4>
                    <div class="row">
                        @if ($gpsBranches->isNotEmpty())
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="attendance-branch-id">@lang('gps_inventory.attendance.branch')</label>
                                    <select id="attendance-branch-id" name="branch_id" class="form-control select-picker" data-live-search="true" required>
                                        <option value="">@lang('gps_inventory.attendance.choose_branch')</option>
                                        @foreach ($gpsBranches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }} — {{ $branch->allowed_radius_in_meters }} @lang('gps_inventory.branches.meters')</option>
                                        @endforeach
                                    </select>
                                    <small id="gps-location-status" class="form-text text-muted">@lang('gps_inventory.attendance.location_required')</small>
                                </div>
                            </div>
                            @if ($maptilerApiKey)
                                <div class="col-md-12 mb-3">
                                    <div id="attendance-gps-map" data-api-key="{{ $maptilerApiKey }}" style="height: 300px; border-radius: .35rem;"></div>
                                </div>
                            @endif
                        @endif
                        <div class="col-md-6">
                            <x-forms.select fieldId="location" :fieldLabel="__('app.location')" fieldName="location"
                                            search="true">
                                @foreach ($location as $locations)
                                    <option @if ($locations->id == $user->employeeDetail->company_address_id) selected
                                            @endif value="{{ $locations->id }}">
                                        {{ $locations->location }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>
                        <div class="col-md-6">
                            <x-forms.select fieldId="work_from_type" :fieldLabel="__('modules.attendance.working_from')"
                                            fieldName="work_from_type" fieldRequired="true"
                                            search="true">
                                <option value="office">@lang('modules.attendance.office')</option>
                                <option value="home">@lang('modules.attendance.home')</option>
                                <option value="other">@lang('modules.attendance.other')</option>
                            </x-forms.select>
                        </div>
                        <div class="col-md-12" id="other_place" style="display:none">
                            <x-forms.text fieldId="working_from" :fieldLabel="__('modules.attendance.otherPlace')"
                                          fieldName="working_from" fieldRequired="true">
                            </x-forms.text>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-clock-in">@lang('modules.attendance.clock_in')</x-forms.button-primary>
    </div>
</x-form>
@else
    <div class="modal-body">
        <x-alert type="danger">@lang('messages.clockInNotAllowed')</x-alert>
    </div>
@endif

@if ($attendanceSettings->radius_check == 'yes' || $attendanceSettings->save_current_location)
    <script>
       setCurrentLocation();
    </script>
@endif

<script>
    (() => {
    $('.select-picker').selectpicker();

    $(function () {
        $('#work_from_type').change(function () {

            ($(this).val() == 'other') ? $('#other_place').show() : $('#other_place').hide();

        });
    });

    const gpsBranches = @json($gpsBranches->map(fn ($branch) => [
        'id' => $branch->id,
        'name' => $branch->name,
        'latitude' => (float) $branch->latitude,
        'longitude' => (float) $branch->longitude,
        'radius' => $branch->allowed_radius_in_meters,
        'maximumAccuracy' => $branch->maximum_accuracy_in_meters,
    ])->values());
    const latitudeField = document.getElementById('current-latitude');
    const longitudeField = document.getElementById('current-longitude');
    const accuracyField = document.getElementById('current-accuracy');
    const branchField = document.getElementById('attendance-branch-id');
    const locationStatus = document.getElementById('gps-location-status');
    let attendanceMap;
    let userMarker;

    const formatDistance = (meters) => Math.round(meters) + ' @lang('gps_inventory.branches.meters')';
    const distanceInMeters = (lat1, lng1, lat2, lng2) => {
        const earthRadius = 6371000;
        const latDelta = (lat2 - lat1) * Math.PI / 180;
        const lngDelta = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(latDelta / 2) ** 2
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(lngDelta / 2) ** 2;

        return earthRadius * 2 * Math.asin(Math.min(1, Math.sqrt(a)));
    };
    const circlePolygon = (longitude, latitude, radius) => {
        const points = [];
        const earthRadius = 6371000;

        for (let index = 0; index <= 64; index++) {
            const bearing = index * 360 / 64 * Math.PI / 180;
            const angularDistance = radius / earthRadius;
            const lat = latitude * Math.PI / 180;
            const lng = longitude * Math.PI / 180;
            const pointLat = Math.asin(Math.sin(lat) * Math.cos(angularDistance) + Math.cos(lat) * Math.sin(angularDistance) * Math.cos(bearing));
            const pointLng = lng + Math.atan2(Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(lat), Math.cos(angularDistance) - Math.sin(lat) * Math.sin(pointLat));
            points.push([pointLng * 180 / Math.PI, pointLat * 180 / Math.PI]);
        }

        return { type: 'Feature', geometry: { type: 'Polygon', coordinates: [points] } };
    };
    const selectedBranch = () => gpsBranches.find((branch) => branch.id === Number(branchField?.value));
    const updateGpsStatus = () => {
        if (!branchField || !latitudeField.value || !longitudeField.value || !accuracyField.value) {
            return;
        }

        const branch = selectedBranch();
        if (!branch) {
            locationStatus.textContent = '@lang('gps_inventory.attendance.choose_branch')';
            return;
        }

        const distance = distanceInMeters(Number(latitudeField.value), Number(longitudeField.value), branch.latitude, branch.longitude);
        const accuracy = Number(accuracyField.value);
        const accepted = distance <= branch.radius && accuracy <= branch.maximumAccuracy;
        locationStatus.className = 'form-text ' + (accepted ? 'text-success' : 'text-danger');
        locationStatus.textContent = accepted
            ? '@lang('gps_inventory.attendance.inside_zone') — ' + formatDistance(distance)
            : '@lang('gps_inventory.attendance.outside_zone') — ' + formatDistance(distance);

        if (attendanceMap && window.maptilersdk) {
            attendanceMap.flyTo({ center: [branch.longitude, branch.latitude], zoom: 16 });
        }
    };
    const captureGpsLocation = () => new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('@lang('gps_inventory.attendance.location_not_supported')'));
            return;
        }

        locationStatus.textContent = '@lang('gps_inventory.attendance.detecting_location')';
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitudeField.value = position.coords.latitude;
                longitudeField.value = position.coords.longitude;
                accuracyField.value = position.coords.accuracy;

                if (attendanceMap && window.maptilersdk) {
                    const coordinates = [position.coords.longitude, position.coords.latitude];
                    if (!userMarker) {
                        userMarker = new maptilersdk.Marker({ color: '#2563eb' }).setLngLat(coordinates).addTo(attendanceMap);
                    } else {
                        userMarker.setLngLat(coordinates);
                    }
                }

                updateGpsStatus();
                resolve();
            },
            () => {
                locationStatus.className = 'form-text text-danger';
                locationStatus.textContent = '@lang('gps_inventory.attendance.location_unavailable')';
                reject(new Error('@lang('gps_inventory.attendance.location_unavailable')'));
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });

    if (branchField) {
        branchField.addEventListener('change', updateGpsStatus);
        captureGpsLocation().catch(() => {});
    }

    if (branchField && '{{ $maptilerApiKey ? '1' : '0' }}' === '1' && window.maptilersdk) {
        const firstBranch = gpsBranches[0];
        maptilersdk.config.apiKey = document.getElementById('attendance-gps-map').dataset.apiKey;
        attendanceMap = new maptilersdk.Map({
            container: 'attendance-gps-map',
            style: maptilersdk.MapStyle.STREETS,
            center: [firstBranch.longitude, firstBranch.latitude],
            zoom: 14,
            navigationControl: true,
            geolocateControl: true,
            scaleControl: true,
        });

        attendanceMap.on('load', () => {
            gpsBranches.forEach((branch) => {
                const sourceId = 'branch-zone-' + branch.id;
                attendanceMap.addSource(sourceId, {
                    type: 'geojson',
                    data: {
                        ...circlePolygon(branch.longitude, branch.latitude, branch.radius),
                    },
                });
                attendanceMap.addLayer({
                    id: sourceId + '-fill',
                    type: 'fill',
                    source: sourceId,
                    paint: {
                        'fill-color': '#2563eb',
                        'fill-opacity': 0.12,
                    },
                });
                attendanceMap.addLayer({
                    id: sourceId + '-outline',
                    type: 'line',
                    source: sourceId,
                    paint: { 'line-color': '#2563eb', 'line-width': 2 },
                });
                new maptilersdk.Marker({ color: '#16a34a' })
                    .setLngLat([branch.longitude, branch.latitude])
                    .setPopup(new maptilersdk.Popup().setText(branch.name))
                    .addTo(attendanceMap);
            });
        });
    }

    $('body').on('click', '#save-clock-in', async function () {
        if (branchField) {
            try {
                await captureGpsLocation();
            } catch (error) {
                return;
            }
        }

        const workingFrom = $('#working_from').val();
        const location = $('#location').val();
        const work_from_type = $('#work_from_type').val();

        const currentLatitude = latitudeField.value;
        const currentLongitude = longitudeField.value;
        const currentAccuracy = accuracyField.value;

        const token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('attendances.store_clock_in') }}",
            type: "POST",
            buttonSelector: "#save-clock-in",
            disableButton: true,
            blockUI: true,
            container: '#clockInForm',
            data: {
                working_from: workingFrom,
                location: location,
                work_from_type: work_from_type,
                branch_id: branchField ? branchField.value : null,
                current_lat: currentLatitude,
                current_lng: currentLongitude,
                current_accuracy: currentAccuracy,
                currentLatitude: currentLatitude,
                currentLongitude: currentLongitude,
                _token: token
            },
            success: function (response) {
                if (response.status === 'success') {
                    window.location.reload();
                }
            }
        });
    });
    })();

</script>
