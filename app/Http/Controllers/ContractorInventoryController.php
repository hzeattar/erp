<?php

namespace App\Http\Controllers;

use App\Models\ContractorInventoryMovement;
use App\Models\ContractorInventorySetting;
use App\Models\ContractorMaterialAllowance;
use App\Models\ContractorProfile;
use App\Models\ContractorProjectAssignment;
use App\Models\ContractorStockRequest;
use App\Models\ContractorStockTransfer;
use App\Models\ContractorWarehouse;
use App\Models\Product;
use App\Models\Project;
use App\Services\ContractorInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContractorInventoryController extends AccountBaseController
{
    public function __construct(private readonly ContractorInventoryService $inventoryService)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $this->ensureAdmin();
        $companyId = user()->company_id;
        $this->pageTitle = 'gps_inventory.inventory.title';
        $this->settings = ContractorInventorySetting::firstOrCreate(['company_id' => $companyId]);
        $this->contractors = ContractorProfile::where('company_id', $companyId)->with(['warehouses', 'assignments.project', 'allowances.product', 'allowances.project'])->orderBy('name')->get();
        $this->warehouses = ContractorWarehouse::where('company_id', $companyId)->with(['contractor', 'stocks.product'])->orderBy('type')->orderBy('name')->get();
        $this->mainWarehouses = $this->warehouses->where('type', 'main');
        $this->contractorWarehouses = $this->warehouses->where('type', 'contractor');
        $this->products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $this->projects = Project::where('company_id', $companyId)->orderBy('project_name')->get();
        $this->requests = ContractorStockRequest::where('company_id', $companyId)->with(['contractor', 'project', 'fromWarehouse', 'toWarehouse', 'product', 'requester', 'decider'])->latest()->limit(100)->get();
        $this->movements = ContractorInventoryMovement::where('company_id', $companyId)->with(['contractor', 'project', 'warehouse', 'product', 'creator'])->latest('movement_date')->latest('id')->limit(100)->get();
        $this->transfers = ContractorStockTransfer::where('company_id', $companyId)->with(['fromWarehouse', 'toWarehouse', 'product', 'creator'])->latest('transfer_date')->latest('id')->limit(50)->get();
        $this->consumptionByContractor = $this->movements->where('movement_type', 'consumption')->groupBy('contractor_id')->map(fn ($rows) => $rows->sum('quantity'));
        return view('contractor-inventory.index', $this->data);
    }

    public function storeContractor(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'company_name' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'notes' => ['nullable', 'string', 'max:4000']]);
        ContractorProfile::create($data + ['company_id' => user()->company_id, 'auto_approve_requests' => $request->boolean('auto_approve_requests'), 'is_active' => true]);
        return back()->with('success', __('gps_inventory.inventory.contractor_saved'));
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['contractor_id' => ['required', 'integer'], 'project_id' => ['required', 'integer'], 'responsibility' => ['nullable', 'string', 'max:500']]);
        $this->ownedContractor($data['contractor_id']); $this->ownedProject($data['project_id']);
        ContractorProjectAssignment::updateOrCreate(['contractor_id' => $data['contractor_id'], 'project_id' => $data['project_id']], $data + ['company_id' => user()->company_id, 'is_active' => true]);
        return back()->with('success', __('gps_inventory.inventory.assignment_saved'));
    }

    public function storeAllowance(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['contractor_id' => ['required', 'integer'], 'project_id' => ['nullable', 'integer'], 'product_id' => ['required', 'integer'], 'allowed_quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->ownedContractor($data['contractor_id']); if (! empty($data['project_id'])) { $this->ownedProject($data['project_id']); } $this->ownedProduct($data['product_id']);
        ContractorMaterialAllowance::create($data + ['company_id' => user()->company_id, 'auto_approve' => $request->boolean('auto_approve'), 'is_active' => true]);
        return back()->with('success', __('gps_inventory.inventory.allowance_saved'));
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:main,contractor'], 'contractor_id' => ['nullable', 'integer'], 'address' => ['nullable', 'string', 'max:255']]);
        if ($data['type'] === 'contractor' && empty($data['contractor_id'])) { throw ValidationException::withMessages(['contractor_id' => __('gps_inventory.inventory.contractor_required')]); }
        if (! empty($data['contractor_id'])) { $this->ownedContractor($data['contractor_id']); }
        ContractorWarehouse::create($data + ['company_id' => user()->company_id, 'is_active' => true]);
        return back()->with('success', __('gps_inventory.inventory.warehouse_saved'));
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['contractor_id' => ['required', 'integer'], 'project_id' => ['nullable', 'integer'], 'from_warehouse_id' => ['required', 'integer'], 'to_warehouse_id' => ['required', 'integer'], 'product_id' => ['required', 'integer'], 'requested_quantity' => ['required', 'integer', 'min:1'], 'request_notes' => ['nullable', 'string', 'max:1000']]);
        $companyId = user()->company_id; $contractor = $this->ownedContractor($data['contractor_id']);
        if (! empty($data['project_id'])) { $this->ownedProject($data['project_id']); } $this->ownedProduct($data['product_id']);
        ContractorWarehouse::where('company_id', $companyId)->where('type', 'main')->findOrFail($data['from_warehouse_id']);
        ContractorWarehouse::where('company_id', $companyId)->where('type', 'contractor')->where('contractor_id', $contractor->id)->findOrFail($data['to_warehouse_id']);
        $allowance = $this->availableAllowance($companyId, $data);
        if (! $allowance || ($allowance->allowed_quantity - $allowance->issued_quantity) < $data['requested_quantity']) { throw ValidationException::withMessages(['requested_quantity' => __('gps_inventory.inventory.allowance_exceeded')]); }
        $settings = ContractorInventorySetting::firstOrCreate(['company_id' => $companyId]);
        $auto = $allowance->auto_approve || $contractor->auto_approve_requests || ($settings->auto_approve_requests && ($settings->auto_approve_max_quantity === null || $data['requested_quantity'] <= $settings->auto_approve_max_quantity));
        ContractorStockRequest::create($data + ['company_id' => $companyId, 'requested_by' => user()->id, 'status' => $auto ? 'approved' : 'pending', 'approved_quantity' => $auto ? $data['requested_quantity'] : null, 'was_auto_approved' => $auto, 'decided_by' => $auto ? user()->id : null, 'decided_at' => $auto ? now() : null]);
        return back()->with('success', $auto ? __('gps_inventory.inventory.request_auto_approved') : __('gps_inventory.inventory.request_saved'));
    }

    public function decideRequest(Request $request, ContractorStockRequest $stockRequest): RedirectResponse
    {
        $this->ensureAdmin(); abort_unless($stockRequest->company_id === user()->company_id, 404);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'approved_quantity' => ['nullable', 'integer', 'min:1'], 'decision_notes' => ['nullable', 'string', 'max:1000']]);
        if ($stockRequest->status !== 'pending') { throw ValidationException::withMessages(['request' => __('gps_inventory.inventory.request_not_pending')]); }
        if ($data['decision'] === 'approved') { $quantity = $data['approved_quantity'] ?? $stockRequest->requested_quantity; if ($quantity > $stockRequest->requested_quantity) { throw ValidationException::withMessages(['approved_quantity' => __('gps_inventory.inventory.approved_quantity_invalid')]); } $stockRequest->update(['status' => 'approved', 'approved_quantity' => $quantity, 'decision_notes' => $data['decision_notes'] ?? null, 'decided_by' => user()->id, 'decided_at' => now()]); }
        else { $stockRequest->update(['status' => 'rejected', 'decision_notes' => $data['decision_notes'] ?? null, 'decided_by' => user()->id, 'decided_at' => now()]); }
        return back()->with('success', __('gps_inventory.inventory.request_decided'));
    }

    public function issueRequest(ContractorStockRequest $stockRequest): RedirectResponse
    {
        $this->ensureAdmin(); abort_unless($stockRequest->company_id === user()->company_id, 404);
        $this->inventoryService->issue($stockRequest, user());
        return back()->with('success', __('gps_inventory.inventory.request_issued'));
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['contractor_id' => ['required', 'integer'], 'warehouse_id' => ['required', 'integer'], 'project_id' => ['nullable', 'integer'], 'product_id' => ['required', 'integer'], 'movement_type' => ['required', 'in:consumption,return'], 'quantity' => ['required', 'integer', 'min:1'], 'main_warehouse_id' => ['nullable', 'integer'], 'movement_date' => ['required', 'date'], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->ownedContractor($data['contractor_id']); if (! empty($data['project_id'])) { $this->ownedProject($data['project_id']); } $this->ownedProduct($data['product_id']);
        if ($data['movement_type'] === 'return' && empty($data['main_warehouse_id'])) { throw ValidationException::withMessages(['main_warehouse_id' => __('gps_inventory.inventory.main_warehouse_required')]); }
        $this->inventoryService->consumeOrReturn($data, user()->company_id, user());
        return back()->with('success', __('gps_inventory.inventory.movement_saved'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureAdmin(); $data = $request->validate(['auto_approve_max_quantity' => ['nullable', 'integer', 'min:1']]);
        ContractorInventorySetting::updateOrCreate(['company_id' => user()->company_id], ['auto_approve_requests' => $request->boolean('auto_approve_requests'), 'auto_approve_max_quantity' => $data['auto_approve_max_quantity'] ?? null]);
        return back()->with('success', __('gps_inventory.inventory.settings_saved'));
    }

    private function availableAllowance(int $companyId, array $data): ?ContractorMaterialAllowance
    {
        return ContractorMaterialAllowance::where('company_id', $companyId)->where('contractor_id', $data['contractor_id'])->where('product_id', $data['product_id'])->where('is_active', true)->where(function ($query) use ($data) { $query->where('project_id', $data['project_id'] ?? null); if (! empty($data['project_id'])) { $query->orWhereNull('project_id'); } })->orderByRaw('project_id is null')->first();
    }
    private function ownedContractor(int $id): ContractorProfile { return ContractorProfile::where('company_id', user()->company_id)->where('is_active', true)->findOrFail($id); }
    private function ownedProject(int $id): Project { return Project::where('company_id', user()->company_id)->findOrFail($id); }
    private function ownedProduct(int $id): Product { return Product::where('company_id', user()->company_id)->findOrFail($id); }
    private function ensureAdmin(): void { abort_unless(user()?->hasRole('admin'), 403); }
}
