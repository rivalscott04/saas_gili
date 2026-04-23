<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_dashboard_summary(): void
    {
        Booking::factory()->create([
            'tour_start_at' => now()->addHours(3),
            'participants' => 2,
            'status' => 'confirmed',
            'gross_amount' => 120,
            'commission_amount' => 20,
            'net_amount' => 100,
            'revenue_amount' => 1700000,
        ]);
        Booking::factory()->create([
            'tour_start_at' => now()->addDays(2),
            'participants' => 4,
            'status' => 'pending',
            'gross_amount' => 90,
            'commission_amount' => 10,
            'net_amount' => 80,
            'revenue_amount' => 1400000,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $summary = (new DashboardService)->summary($admin);

        $this->assertSame(2, $summary['total_bookings']);
        $this->assertSame(2, $summary['upcoming_tours']);
        $this->assertSame(6, $summary['guests_expected']);
        $this->assertSame(1, $summary['needs_attention']);
        $this->assertSame(120.0, $summary['gross_sales']);
        $this->assertSame(100.0, $summary['net_revenue']);
        $this->assertSame(1700000.0, $summary['revenue_idr']);
        $this->assertArrayNotHasKey('commission_total', $summary);
    }

    public function test_superadmin_summary_can_be_scoped_to_specific_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Booking::factory()->create([
            'tenant_id' => $tenantA->id,
            'tour_start_at' => now()->addHours(3),
            'participants' => 2,
            'status' => 'confirmed',
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenantB->id,
            'tour_start_at' => now()->addDays(2),
            'participants' => 4,
            'status' => 'pending',
        ]);

        $superadmin = User::factory()->create(['role' => 'superadmin', 'tenant_id' => null]);
        $summary = (new DashboardService)->summary($superadmin, $tenantA->id);

        $this->assertSame(1, $summary['total_bookings']);
        $this->assertSame(2, $summary['guests_expected']);
    }
}
