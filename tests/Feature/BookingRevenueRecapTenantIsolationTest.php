<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRevenueRecapTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_only_sees_their_own_tenant_recap_data(): void
    {
        $tenantA = Tenant::factory()->create(['code' => 'tenant-a']);
        $tenantB = Tenant::factory()->create(['code' => 'tenant-b']);

        $tenantAdminA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'tenant_admin',
        ]);

        Booking::factory()->create([
            'tenant_id' => $tenantA->id,
            'channel' => 'getyourguide',
            'status' => 'confirmed',
            'currency' => 'IDR',
            'net_amount' => 100000,
            'revenue_amount' => 100000,
            'tour_start_at' => now(),
        ]);

        Booking::factory()->create([
            'tenant_id' => $tenantB->id,
            'channel' => 'viator',
            'status' => 'confirmed',
            'currency' => 'IDR',
            'net_amount' => 999000,
            'revenue_amount' => 999000,
            'tour_start_at' => now(),
        ]);

        $this->actingAs($tenantAdminA)
            ->get(route('bookings.recap'))
            ->assertOk()
            ->assertSee('GETYOURGUIDE')
            ->assertSee('100,000')
            ->assertDontSee('VIATOR')
            ->assertDontSee('999,000');
    }
}
