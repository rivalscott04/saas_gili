<?php

namespace Tests\Feature;

use App\Models\ChannelSyncLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelSyncLogsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_open_sync_logs_page(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        ChannelSyncLog::query()->create([
            'tenant_id' => $tenant->id,
            'event_type' => 'webhook.received',
            'direction' => 'inbound',
            'status' => 'success',
            'message' => 'Sample log',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('channel-sync-logs.index'))
            ->assertOk()
            ->assertSee('Sync Logs')
            ->assertSee('webhook.received');
    }
}
