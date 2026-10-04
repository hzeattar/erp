@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Contractor Inventory</h4>
            <span class="text-muted">Move stock from the main warehouse to contractor sites</span>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="bg-white rounded p-4 shadow-sm mb-4">
                    <h5>Add warehouse</h5>
                    <form method="POST" action="{{ route('contractor-inventory.warehouses.store') }}">
                        @csrf
                        <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
                        <div class="form-group"><label>Type</label><select name="type" class="form-control" required><option value="main">Main</option><option value="contractor">Contractor</option></select></div>
                        <div class="form-group"><label>Address</label><input name="address" class="form-control"></div>
                        <button class="btn btn-primary">Save warehouse</button>
                    </form>
                </div>
                <div class="bg-white rounded p-4 shadow-sm">
                    <h5>New stock transfer</h5>
                    <form method="POST" action="{{ route('contractor-inventory.transfers.store') }}">
                        @csrf
                        <div class="form-group"><label>From warehouse</label><select name="from_warehouse_id" class="form-control" required>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ ucfirst($warehouse->type) }})</option>@endforeach</select></div>
                        <div class="form-group"><label>To contractor warehouse</label><select name="to_warehouse_id" class="form-control" required>@foreach ($warehouses->where('type', 'contractor') as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>Product</label><select name="product_id" class="form-control" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>@endforeach</select></div>
                        <div class="form-row"><div class="form-group col-md-6"><label>Quantity</label><input name="quantity" type="number" min="1" class="form-control" required></div><div class="form-group col-md-6"><label>Date</label><input name="transfer_date" type="date" class="form-control" required value="{{ now()->toDateString() }}"></div></div>
                        <div class="form-group"><label>Reference</label><input name="reference" class="form-control"></div>
                        <button class="btn btn-success">Complete transfer</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="bg-white rounded p-4 shadow-sm mb-4">
                    <h5>Current stock</h5>
                    <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Warehouse</th><th>Type</th><th>Product</th><th>SKU</th><th>Quantity</th></tr></thead><tbody>
                    @foreach ($warehouses as $warehouse)
                        @forelse ($warehouse->stocks as $stock)
                            <tr><td>{{ $warehouse->name }}</td><td>{{ ucfirst($warehouse->type) }}</td><td>{{ $stock->product->name }}</td><td>{{ $stock->product->sku }}</td><td><strong>{{ $stock->quantity }}</strong></td></tr>
                        @empty
                            <tr><td>{{ $warehouse->name }}</td><td>{{ ucfirst($warehouse->type) }}</td><td colspan="3" class="text-muted">No stock recorded.</td></tr>
                        @endforelse
                    @endforeach
                    </tbody></table></div>
                </div>
                <div class="bg-white rounded p-4 shadow-sm"><h5>Transfer history</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>From</th><th>To</th><th>Product</th><th>Qty</th><th>By</th></tr></thead><tbody>
                    @forelse ($transfers as $transfer)<tr><td>{{ $transfer->transfer_date->format('Y-m-d') }}</td><td>{{ $transfer->fromWarehouse->name }}</td><td>{{ $transfer->toWarehouse->name }}</td><td>{{ $transfer->product->name }}</td><td>{{ $transfer->quantity }}</td><td>{{ $transfer->creator?->name ?? 'System' }}</td></tr>@empty<tr><td colspan="6" class="text-muted text-center">No transfers yet.</td></tr>@endforelse
                </tbody></table></div></div>
            </div>
        </div>
    </div>
@endsection
