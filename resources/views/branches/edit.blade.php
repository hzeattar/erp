@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="bg-white rounded p-4 shadow-sm" style="max-width: 760px;">
            <div class="d-flex justify-content-between mb-3"><h4>Edit GPS branch</h4><a href="{{ route('branches.index') }}">Back</a></div>
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ route('branches.update', $branch) }}">
                @csrf @method('PUT')
                <div class="form-group"><label>Branch name</label><input name="name" class="form-control" required value="{{ old('name', $branch->name) }}"></div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Latitude</label><input name="latitude" type="number" step="0.0000001" class="form-control" required value="{{ old('latitude', $branch->latitude) }}"></div>
                    <div class="form-group col-md-6"><label>Longitude</label><input name="longitude" type="number" step="0.0000001" class="form-control" required value="{{ old('longitude', $branch->longitude) }}"></div>
                </div>
                <div class="form-group"><label>Allowed radius (meters)</label><input name="allowed_radius_in_meters" type="number" min="1" class="form-control" required value="{{ old('allowed_radius_in_meters', $branch->allowed_radius_in_meters) }}"></div>
                <div class="form-group">
                    <label>Allowed employees</label>
                    <select name="employee_ids[]" class="form-control select-picker" multiple data-live-search="true" data-size="8">
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($branch->users->contains('id', $employee->id))>{{ $employee->name }} — {{ $employee->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $branch->is_active))><label class="form-check-label" for="is_active">Active for check-in</label></div>
                <button class="btn btn-primary" type="submit">Update branch</button>
            </form>
        </div>
    </div>
@endsection
