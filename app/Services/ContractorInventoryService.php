<?php

namespace App\Services;

use App\Models\ContractorInventoryMovement;
use App\Models\ContractorInventoryStock;
use App\Models\ContractorMaterialAllowance;
use App\Models\ContractorStockRequest;
use App\Models\ContractorStockTransfer;
use App\Models\ContractorWarehouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractorInventoryService
{
    public function issue(ContractorStockRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor): void {
            $request = ContractorStockRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status !== 'approved') {
                throw ValidationException::withMessages(['request' => __('gps_inventory.inventory.request_must_be_approved')]);
            }

            $quantity = $request->approved_quantity;
            $source = ContractorInventoryStock::where('company_id', $request->company_id)
                ->where('warehouse_id', $request->from_warehouse_id)
                ->where('product_id', $request->product_id)
                ->lockForUpdate()
                ->first();

            if (! $source || $source->quantity < $quantity) {
                throw ValidationException::withMessages(['request' => __('gps_inventory.api.source_not_enough')]);
            }

            $allowance = ContractorMaterialAllowance::where('company_id', $request->company_id)
                ->where('contractor_id', $request->contractor_id)
                ->where('product_id', $request->product_id)
                ->where('is_active', true)
                ->where(function ($query) use ($request) {
                    $query->where('project_id', $request->project_id);

                    if ($request->project_id !== null) {
                        $query->orWhereNull('project_id');
                    }
                })
                ->orderByRaw('project_id is null')
                ->lockForUpdate()
                ->first();

            if (! $allowance || ($allowance->allowed_quantity - $allowance->issued_quantity) < $quantity) {
                throw ValidationException::withMessages(['request' => __('gps_inventory.inventory.allowance_exceeded')]);
            }

            $source->decrement('quantity', $quantity);

            $destination = ContractorInventoryStock::firstOrCreate([
                'company_id' => $request->company_id,
                'warehouse_id' => $request->to_warehouse_id,
                'product_id' => $request->product_id,
            ], ['quantity' => 0]);
            $destination->increment('quantity', $quantity);

            $allowance->increment('issued_quantity', $quantity);

            ContractorStockTransfer::create([
                'company_id' => $request->company_id,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'product_id' => $request->product_id,
                'quantity' => $quantity,
                'transfer_date' => now()->toDateString(),
                'status' => 'completed',
                'reference' => 'REQ-'.$request->id,
                'notes' => $request->request_notes,
                'created_by' => $actor->id,
            ]);

            ContractorInventoryMovement::create([
                'company_id' => $request->company_id,
                'contractor_id' => $request->contractor_id,
                'warehouse_id' => $request->to_warehouse_id,
                'project_id' => $request->project_id,
                'product_id' => $request->product_id,
                'movement_type' => 'receipt',
                'quantity' => $quantity,
                'reference' => 'REQ-'.$request->id,
                'notes' => $request->request_notes,
                'stock_request_id' => $request->id,
                'created_by' => $actor->id,
                'movement_date' => now()->toDateString(),
            ]);

            $request->update(['status' => 'issued', 'issued_at' => now(), 'issued_by' => $actor->id]);
        });
    }

    public function consumeOrReturn(array $data, int $companyId, User $actor): void
    {
        DB::transaction(function () use ($data, $companyId, $actor): void {
            $warehouse = ContractorWarehouse::where('company_id', $companyId)
                ->where('type', 'contractor')
                ->where('contractor_id', $data['contractor_id'])
                ->lockForUpdate()
                ->findOrFail($data['warehouse_id']);
            $stock = ContractorInventoryStock::where('company_id', $companyId)
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->quantity < $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => __('gps_inventory.api.source_not_enough')]);
            }

            $stock->decrement('quantity', $data['quantity']);

            if ($data['movement_type'] === 'return') {
                $main = ContractorWarehouse::where('company_id', $companyId)
                    ->where('type', 'main')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->findOrFail($data['main_warehouse_id']);
                $mainStock = ContractorInventoryStock::firstOrCreate([
                    'company_id' => $companyId,
                    'warehouse_id' => $main->id,
                    'product_id' => $data['product_id'],
                ], ['quantity' => 0]);
                $mainStock->increment('quantity', $data['quantity']);
            }

            ContractorInventoryMovement::create([
                'company_id' => $companyId,
                'contractor_id' => $data['contractor_id'],
                'warehouse_id' => $warehouse->id,
                'project_id' => $data['project_id'] ?? null,
                'product_id' => $data['product_id'],
                'movement_type' => $data['movement_type'],
                'quantity' => $data['quantity'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'movement_date' => $data['movement_date'],
            ]);
        });
    }
}
