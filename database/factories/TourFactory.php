<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->sentence(3),
            'code' => strtoupper(fake()->unique()->bothify('TR-###')),
            'description' => fake()->optional()->sentence(),
            'default_max_pax_per_day' => fake()->optional()->numberBetween(5, 40),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
