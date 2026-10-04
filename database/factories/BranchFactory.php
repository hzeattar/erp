<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Branch',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'allowed_radius_in_meters' => 50,
        ];
    }
}
