<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (Booking $booking): void {
            if ($booking->user_id !== null) {
                $user = User::query()->find($booking->user_id);
                if ($user !== null) {
                    $booking->tenant_id = $user->tenant_id;
                }
            }
        })->afterCreating(function (Booking $booking): void {
            if ($booking->customer_id !== null) {
                Customer::query()
                    ->whereKey($booking->customer_id)
                    ->update(['tenant_id' => $booking->tenant_id]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'tour_id' => function (array $attributes): ?int {
                $tenantId = $attributes['tenant_id'] ?? null;
                if ($tenantId === null) {
                    return null;
                }

                return Tour::factory()->create([
                    'tenant_id' => $tenantId,
                    'name' => fake()->unique()->sentence(3),
                ])->id;
            },
            'customer_id' => function (array $attributes): int {
                return Customer::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                ])->id;
            },
            'tour_name' => fake()->randomElement(['Bali Sunrise Tour', 'Komodo Day Trip', 'Ubud Waterfall']),
            'customer_name' => fake()->name(), // Backward compatibility for existing frontend contract
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'tour_start_at' => fake()->dateTimeBetween('now', '+10 days'),
            'location' => fake()->city(),
            'guide_name' => fake()->name(),
            'status' => fake()->randomElement(['standby', 'confirmed', 'pending', 'cancelled']),
            'booking_source' => 'manual',
            'participants' => fake()->numberBetween(1, 8),
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
            'assigned_to_name' => fake()->optional()->name(),
            'tags' => fake()->boolean(40) ? [fake()->randomElement(['vip', 'pickup', 'reschedule'])] : [],
            'needs_attention' => fake()->boolean(20),
        ];
    }
}
