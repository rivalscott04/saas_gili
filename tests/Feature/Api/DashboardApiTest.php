<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class DashboardApiTest extends AuthenticatedApiTestCase
{
    public function test_it_returns_dashboard_summary_and_lists(): void
    {
        Booking::factory()->create([
            'tour_start_at' => now()->addHours(2),
            'participants' => 3,
            'status' => 'pending',
        ]);
        Booking::factory()->create([
            'tour_start_at' => now()->addDays(3),
            'participants' => 2,
            'status' => 'confirmed',
        ]);

        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.total_bookings', 2)
            ->assertJsonMissingPath('data.net_revenue');

        $this->getJson('/api/v1/dashboard/urgent-bookings?limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/dashboard/recent-bookings?limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_superadmin_can_filter_dashboard_by_tenant_scope(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'tenant_id' => null]);
        Sanctum::actingAs($superadmin);

        Booking::factory()->create([
            'tenant_id' => $tenantA->id,
            'tour_start_at' => now()->addHours(2),
            'participants' => 3,
            'status' => 'pending',
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenantB->id,
            'tour_start_at' => now()->addDays(3),
            'participants' => 2,
            'status' => 'confirmed',
        ]);

        $this->getJson('/api/v1/dashboard/summary?tenant_id='.$tenantA->id)
            ->assertOk()
            ->assertJsonPath('data.total_bookings', 1)
            ->assertJsonStructure(['data' => ['gross_sales', 'net_revenue', 'revenue_idr']])
            ->assertJsonMissingPath('data.commission_total');

        $this->getJson('/api/v1/dashboard/summary?tenant_id='.$tenantB->id)
            ->assertOk()
            ->assertJsonPath('data.total_bookings', 1);
    }

    public function test_tenant_user_cannot_escalate_dashboard_scope_with_tenant_param(): void
    {
        $tenantA = $this->apiUser->tenant;
        $tenantB = Tenant::factory()->create();

        Booking::factory()->create([
            'tenant_id' => $tenantA?->id,
            'tour_start_at' => now()->addHours(2),
            'participants' => 3,
            'status' => 'pending',
            'user_id' => $this->apiUser->id,
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenantB->id,
            'tour_start_at' => now()->addDays(3),
            'participants' => 2,
            'status' => 'confirmed',
        ]);

        $this->getJson('/api/v1/dashboard/summary?tenant_id='.$tenantB->id)
            ->assertOk()
            ->assertJsonPath('data.total_bookings', 1);
    }
}
