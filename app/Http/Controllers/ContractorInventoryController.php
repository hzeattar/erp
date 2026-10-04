<?php

namespace App\Http\Controllers;

use App\Models\ContractorInventoryStock;
use App\Models\ContractorStockTransfer;
use App\Models\ContractorWarehouse;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContractorInventoryController extends AccountBaseController
{
    public function index(): View
    {
        $this->ensureAdmin();

        $companyId = user()->company_id;

        $this->pageTitle = 'Contractor Inventory';
        $this->warehouses = ContractorWarehouse::where('company_id', $companyId)
            ->with(['stocks.product'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $this->products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $this->transfers = ContractorStockTransfer::where('company_id', $companyId)
            ->with(['fromWarehouse', 'toWarehouse', 'product', 'creator'])
            ->latest('transfer_date')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('contractor-inventory.index', $this->data);
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:main,contractor'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        ContractorWarehouse::create($data + [
            'company_id' => user()->company_id,
            'is_active' => true,
        ]);

        return back()->with('success', 'Warehouse created successfully.');
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'integer', 'exists:contractor_warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'different:from_warehouse_id', 'exists:contractor_warehouses,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $companyId = user()->company_id;

        DB::transaction(function () use ($data, $companyId): void {
            $from = ContractorWarehouse::where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($data['from_warehouse_id']);
            $to = ContractorWarehouse::where('company_id', $companyId)->findOrFail($data['to_warehouse_id']);
            $product = Product::where('company_id', $companyId)->findOrFail($data['product_id']);

            if ($to->type !== 'contractor') {
                throw ValidationException::withMessages([
                    'to_warehouse_id' => 'The destination warehouse must be a contractor warehouse.',
                ]);
            }

            $sourceStock = ContractorInventoryStock::where('company_id', $companyId)
                ->where('warehouse_id', $from->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (!$sourceStock || $sourceStock->quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'The source warehouse does not have enough stock for this transfer.',
                ]);
            }

            $sourceStock->decrement('quantity', $data['quantity']);

            $destinationStock = ContractorInventoryStock::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'warehouse_id' => $to->id,
                    'product_id' => $product->id,
                ],
                ['quantity' => 0]
            );
            $destinationStock->increment('quantity', $data['quantity']);

            ContractorStockTransfer::create($data + [
                'company_id' => $companyId,
                'status' => 'completed',
                'created_by' => user()->id,
            ]);
        });

        return back()->with('success', 'Stock transfer completed successfully.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(user()?->hasRole('admin'), 403);
    }
}
