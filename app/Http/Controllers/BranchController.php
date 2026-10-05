<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends AccountBaseController
{
    public function index(): View
    {
        $this->ensureAdmin();

        $this->pageTitle = 'gps_inventory.branches.title';
        $this->branches = Branch::withCount('users')->latest()->get();
        $this->employees = $this->employeeOptions();

        return view('branches.index', $this->data);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $this->validated($request);

        $branch = Branch::create([
            'company_id' => user()->company_id,
            'name' => $data['name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'allowed_radius_in_meters' => $data['allowed_radius_in_meters'],
            'maximum_accuracy_in_meters' => $data['maximum_accuracy_in_meters'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $branch->users()->sync($data['employee_ids'] ?? []);

        return redirect()->route('branches.index')->with('success', __('gps_inventory.branches.saved'));
    }

    public function edit(Branch $branch): View
    {
        $this->ensureAdmin();
        $this->ensureCompany($branch);

        $this->pageTitle = 'gps_inventory.branches.edit_title';
        $this->branch = $branch->load('users:id,name');
        $this->employees = $this->employeeOptions();

        return view('branches.edit', $this->data);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->ensureAdmin();
        $this->ensureCompany($branch);
        $data = $this->validated($request);

        $branch->update([
            'name' => $data['name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'allowed_radius_in_meters' => $data['allowed_radius_in_meters'],
            'maximum_accuracy_in_meters' => $data['maximum_accuracy_in_meters'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $branch->users()->sync($data['employee_ids'] ?? []);

        return redirect()->route('branches.index')->with('success', __('gps_inventory.branches.updated'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->ensureAdmin();
        $this->ensureCompany($branch);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', __('gps_inventory.branches.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'allowed_radius_in_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            'maximum_accuracy_in_meters' => ['required', 'integer', 'min:5', 'max:10000'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', user()->company_id)),
            ],
        ]);
    }

    private function employeeOptions()
    {
        return User::where('company_id', user()->company_id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'employee'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function ensureCompany(Branch $branch): void
    {
        abort_unless((int) $branch->company_id === (int) user()->company_id, 404);
    }

    private function ensureAdmin(): void
    {
        abort_unless(user()?->hasRole('admin'), 403);
    }
}
