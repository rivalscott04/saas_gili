<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Customer;

class CustomerApiTest extends AuthenticatedApiTestCase
{
    public function test_it_lists_customers_with_search_and_booking_count(): void
    {
        $john = Customer::factory()->create([
            'full_name' => 'John Carter',
            'email' => 'john@example.com',
        ]);
        Customer::factory()->create([
            'full_name' => 'Alice Parker',
            'email' => 'alice@example.com',
        ]);
        Booking::factory()->create(['customer_id' => $john->id]);
        Booking::factory()->create(['customer_id' => $john->id]);

        $response = $this->getJson('/api/v1/customers?search=john');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'John Carter')
            ->assertJsonPath('data.0.bookings_count', 2);
    }
}
