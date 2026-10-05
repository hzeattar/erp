@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">@lang('gps_inventory.branches.title')</h4>
            <span class="text-muted">@lang('gps_inventory.branches.subtitle')</span>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="bg-white rounded p-4 shadow-sm">
                    <h5 class="mb-3">@lang('gps_inventory.branches.add')</h5>
                    <form method="POST" action="{{ route('branches.store') }}">
                        @csrf
                        <div class="form-group"><label>@lang('gps_inventory.branches.name')</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label>@lang('gps_inventory.branches.latitude')</label><input name="latitude" type="number" step="0.0000001" class="form-control" required value="{{ old('latitude') }}"></div>
                            <div class="form-group col-md-6"><label>@lang('gps_inventory.branches.longitude')</label><input name="longitude" type="number" step="0.0000001" class="form-control" required value="{{ old('longitude') }}"></div>
                        </div>
                        <div class="form-group"><label>@lang('gps_inventory.branches.radius') (@lang('gps_inventory.branches.meters'))</label><input name="allowed_radius_in_meters" type="number" min="1" class="form-control" required value="{{ old('allowed_radius_in_meters', 250) }}"></div>
                        <div class="form-group"><label>@lang('gps_inventory.branches.maximum_accuracy') (@lang('gps_inventory.branches.meters'))</label><input name="maximum_accuracy_in_meters" type="number" min="5" class="form-control" required value="{{ old('maximum_accuracy_in_meters', 100) }}"><small class="form-text text-muted">@lang('gps_inventory.branches.maximum_accuracy_help')</small></div>
                        @include('branches.partials.map-picker')
                        <div class="form-group">
                            <label>@lang('gps_inventory.branches.employees')</label>
                            <select name="employee_ids[]" class="form-control select-picker" multiple data-live-search="true" data-size="8">
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} — {{ $employee->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">@lang('gps_inventory.branches.save')</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="bg-white rounded p-4 shadow-sm">
                    <h5 class="mb-3">@lang('gps_inventory.branches.configured')</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>@lang('gps_inventory.branches.name')</th><th>@lang('gps_inventory.branches.coordinates')</th><th>@lang('gps_inventory.branches.radius')</th><th>@lang('gps_inventory.branches.maximum_accuracy')</th><th>@lang('gps_inventory.branches.employee_count')</th><th>@lang('gps_inventory.branches.actions')</th></tr></thead>
                            <tbody>
                            @forelse ($branches as $branch)
                                <tr>
                                    <td>{{ $branch->name }}</td>
                                    <td>{{ $branch->latitude }}, {{ $branch->longitude }}</td>
                                    <td>{{ $branch->allowed_radius_in_meters }} @lang('gps_inventory.branches.meters')</td>
                                    <td>{{ $branch->maximum_accuracy_in_meters }} @lang('gps_inventory.branches.meters')</td>
                                    <td>{{ $branch->users_count }}</td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('branches.edit', $branch) }}">@lang('gps_inventory.branches.edit')</a>
                                        <form class="d-inline" method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm(@json(__('gps_inventory.branches.confirm_delete')))" >
                                            @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">@lang('gps_inventory.branches.delete')</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">@lang('gps_inventory.branches.empty')</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
