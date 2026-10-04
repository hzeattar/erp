@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">GPS Branches & Geofences</h4>
            <span class="text-muted">Assign employees to allowed attendance locations</span>
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
                    <h5 class="mb-3">Add branch</h5>
                    <form method="POST" action="{{ route('branches.store') }}">
                        @csrf
                        <div class="form-group"><label>Branch name</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label>Latitude</label><input name="latitude" type="number" step="0.0000001" class="form-control" required value="{{ old('latitude') }}"></div>
                            <div class="form-group col-md-6"><label>Longitude</label><input name="longitude" type="number" step="0.0000001" class="form-control" required value="{{ old('longitude') }}"></div>
                        </div>
                        <div class="form-group"><label>Allowed radius (meters)</label><input name="allowed_radius_in_meters" type="number" min="1" class="form-control" required value="{{ old('allowed_radius_in_meters', 250) }}"></div>
                        <div class="form-group">
                            <label>Allowed employees</label>
                            <select name="employee_ids[]" class="form-control select-picker" multiple data-live-search="true" data-size="8">
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} — {{ $employee->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Save branch</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="bg-white rounded p-4 shadow-sm">
                    <h5 class="mb-3">Configured branches</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>Name</th><th>Coordinates</th><th>Radius</th><th>Employees</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($branches as $branch)
                                <tr>
                                    <td>{{ $branch->name }}</td>
                                    <td>{{ $branch->latitude }}, {{ $branch->longitude }}</td>
                                    <td>{{ $branch->allowed_radius_in_meters }} m</td>
                                    <td>{{ $branch->users_count }}</td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('branches.edit', $branch) }}">Edit</a>
                                        <form class="d-inline" method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm('Delete this branch?')">
                                            @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No branches configured.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
