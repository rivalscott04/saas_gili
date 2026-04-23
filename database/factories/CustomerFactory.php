<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'external_source' => 'getyourguide',
            'external_customer_ref' => (string) fake()->unique()->numberBetween(100000, 999999),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country_code' => fake()->countryCode(),
            'raw_payload' => [
                'source' => 'factory',
            ],
        ];
    }
}
