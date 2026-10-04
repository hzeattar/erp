<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory()->state(['type' => 'contractor']),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'transfer_date' => now()->toDateString(),
        ];
    }
}
