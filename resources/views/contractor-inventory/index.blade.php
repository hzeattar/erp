@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">@lang('gps_inventory.inventory.title')</h4>
            <span class="text-muted">@lang('gps_inventory.inventory.subtitle')</span>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="bg-white rounded p-4 shadow-sm mb-4">
                    <h5>@lang('gps_inventory.inventory.add_warehouse')</h5>
                    <form method="POST" action="{{ route('contractor-inventory.warehouses.store') }}">
                        @csrf
                        <div class="form-group"><label>@lang('gps_inventory.inventory.name')</label><input name="name" class="form-control" required></div>
                        <div class="form-group"><label>@lang('gps_inventory.inventory.type')</label><select name="type" class="form-control" required><option value="main">@lang('gps_inventory.inventory.main')</option><option value="contractor">@lang('gps_inventory.inventory.contractor')</option></select></div>
                        <div class="form-group"><label>@lang('gps_inventory.inventory.address')</label><input name="address" class="form-control"></div>
                        <button class="btn btn-primary">@lang('gps_inventory.inventory.save_warehouse')</button>
                    </form>
                </div>
                <div class="bg-white rounded p-4 shadow-sm">
                    <h5>@lang('gps_inventory.inventory.new_transfer')</h5>
                    <form method="POST" action="{{ route('contractor-inventory.transfers.store') }}">
                        @csrf
                        <div class="form-group"><label>@lang('gps_inventory.inventory.from_warehouse')</label><select name="from_warehouse_id" class="form-control" required>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }} (@lang('gps_inventory.inventory.' . $warehouse->type))</option>@endforeach</select></div>
                        <div class="form-group"><label>@lang('gps_inventory.inventory.to_warehouse')</label><select name="to_warehouse_id" class="form-control" required>@foreach ($warehouses->where('type', 'contractor') as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>@lang('gps_inventory.inventory.product')</label><select name="product_id" class="form-control" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>@endforeach</select></div>
                        <div class="form-row"><div class="form-group col-md-6"><label>@lang('gps_inventory.inventory.quantity')</label><input name="quantity" type="number" min="1" class="form-control" required></div><div class="form-group col-md-6"><label>@lang('gps_inventory.inventory.date')</label><input name="transfer_date" type="date" class="form-control" required value="{{ now()->toDateString() }}"></div></div>
                        <div class="form-group"><label>@lang('gps_inventory.inventory.reference')</label><input name="reference" class="form-control"></div>
                        <button class="btn btn-success">@lang('gps_inventory.inventory.complete_transfer')</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="bg-white rounded p-4 shadow-sm mb-4">
                    <h5>@lang('gps_inventory.inventory.current_stock')</h5>
                    <div class="table-responsive"><table class="table table-hover"><thead><tr><th>@lang('gps_inventory.inventory.warehouse')</th><th>@lang('gps_inventory.inventory.type')</th><th>@lang('gps_inventory.inventory.product')</th><th>@lang('gps_inventory.inventory.sku')</th><th>@lang('gps_inventory.inventory.quantity')</th></tr></thead><tbody>
                    @foreach ($warehouses as $warehouse)
                        @forelse ($warehouse->stocks as $stock)
                            <tr><td>{{ $warehouse->name }}</td><td>@lang('gps_inventory.inventory.' . $warehouse->type)</td><td>{{ $stock->product->name }}</td><td>{{ $stock->product->sku }}</td><td><strong>{{ $stock->quantity }}</strong></td></tr>
                        @empty
                            <tr><td>{{ $warehouse->name }}</td><td>@lang('gps_inventory.inventory.' . $warehouse->type)</td><td colspan="3" class="text-muted">@lang('gps_inventory.inventory.no_stock')</td></tr>
                        @endforelse
                    @endforeach
                    </tbody></table></div>
                </div>
                <div class="bg-white rounded p-4 shadow-sm"><h5>@lang('gps_inventory.inventory.transfer_history')</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>@lang('gps_inventory.inventory.date')</th><th>@lang('gps_inventory.inventory.from')</th><th>@lang('gps_inventory.inventory.to')</th><th>@lang('gps_inventory.inventory.product')</th><th>@lang('gps_inventory.inventory.quantity')</th><th>@lang('gps_inventory.inventory.by')</th></tr></thead><tbody>
                    @forelse ($transfers as $transfer)<tr><td>{{ $transfer->transfer_date->format('Y-m-d') }}</td><td>{{ $transfer->fromWarehouse->name }}</td><td>{{ $transfer->toWarehouse->name }}</td><td>{{ $transfer->product->name }}</td><td>{{ $transfer->quantity }}</td><td>{{ $transfer->creator?->name ?? __('gps_inventory.inventory.system') }}</td></tr>@empty<tr><td colspan="6" class="text-muted text-center">@lang('gps_inventory.inventory.no_transfers')</td></tr>@endforelse
                </tbody></table></div></div>
            </div>
        </div>
    </div>
@endsection
