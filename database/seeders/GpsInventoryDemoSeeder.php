<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ContractorInventoryStock;
use App\Models\ContractorWarehouse;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class GpsInventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $employee = User::withoutGlobalScopes()->where('email', 'employee@example.com')->first();

        $branch = Branch::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'name' => 'Cairo Main Branch'],
            [
                'latitude' => 30.044420,
                'longitude' => 31.235712,
                'allowed_radius_in_meters' => 250,
                'is_active' => true,
            ]
        );

        if ($employee) {
            $branch->users()->syncWithoutDetaching([$employee->id]);
        }

        $mainWarehouse = ContractorWarehouse::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'name' => 'Main Warehouse'],
            ['type' => 'main', 'address' => 'Main company warehouse', 'is_active' => true]
        );
        $contractorWarehouse = ContractorWarehouse::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'name' => 'Contractor Demo Warehouse'],
            ['type' => 'contractor', 'address' => 'Demo contractor site', 'is_active' => true]
        );

        $product = Product::withoutGlobalScopes()->where('company_id', $companyId)->first();

        if (!$product) {
            $product = new Product();
            $product->company_id = $companyId;
            $product->name = 'Demo Safety Equipment';
            $product->sku = 'DEMO-SAFETY-001';
            $product->price = 0;
            $product->description = 'Demo product for contractor stock testing.';
            $product->taxes = '[]';
            $product->save();
        }

        ContractorInventoryStock::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $companyId,
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $product->id,
            ],
            ['quantity' => 100]
        );
        ContractorInventoryStock::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $companyId,
                'warehouse_id' => $contractorWarehouse->id,
                'product_id' => $product->id,
            ],
            ['quantity' => 0]
        );
    }
}
