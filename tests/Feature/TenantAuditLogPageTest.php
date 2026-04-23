<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_filter_audit_logs_by_tour_and_date(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tourA = Tour::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Tour A']);
        $tourB = Tour::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Tour B']);

        TenantAuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'actor_user_id' => $admin->id,
            'event_type' => 'capacity.updated',
            'entity_type' => 'tour_day_capacity',
            'entity_id' => 1,
            'tour_id' => $tourA->id,
            'service_date' => '2026-09-01',
            'context' => ['max_pax' => 10],
            'occurred_at' => '2026-09-01 08:00:00',
        ]);
        TenantAuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'actor_user_id' => $admin->id,
            'event_type' => 'capacity.updated',
            'entity_type' => 'tour_day_capacity',
            'entity_id' => 2,
            'tour_id' => $tourB->id,
            'service_date' => '2026-09-02',
            'context' => ['max_pax' => 20],
            'occurred_at' => '2026-09-02 08:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('tenant-audit-logs.index', [
                'tour_id' => $tourA->id,
                'from' => '2026-09-01',
                'to' => '2026-09-01',
            ]))
            ->assertOk()
            ->assertSee('Tour A')
            ->assertDontSee('2026-09-02');
    }
}
