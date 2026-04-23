<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRevenueRecapPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_open_recap_page_with_filters(): void
    {
        $admin = User::factory()->create(['role' => 'tenant_admin']);
        Booking::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'status' => 'confirmed',
            'channel' => 'getyourguide',
            'currency' => 'EUR',
            'net_amount' => 100,
            'fx_rate_to_idr' => 17000,
            'revenue_amount' => 1700000,
            'tour_start_at' => now()->setDate(2026, 4, 20),
        ]);

        $this->actingAs($admin)
            ->get(route('bookings.recap', [
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
                'channel' => 'getyourguide',
            ]))
            ->assertOk()
            ->assertSee('Booking Revenue Recap')
            ->assertSee('GETYOURGUIDE')
            ->assertSee('1,700,000');
    }
}
