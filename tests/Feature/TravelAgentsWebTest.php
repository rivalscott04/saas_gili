<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TravelAgent;
use App\Models\User;
use App\Services\TravelAgentConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelAgentsWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_open_travel_agents_page_and_connect(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        $service = app(TravelAgentConnectionService::class);
        $service->ensureDefaultTravelAgents();
        $agent = TravelAgent::query()->where('code', 'getyourguide')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('travel-agents.index'))
            ->assertOk()
            ->assertSee('Travel Agents');

        $this->actingAs($admin)
            ->post(route('travel-agents.connect', $agent), [
                'api_key' => 'tenant-key',
                'api_secret' => 'tenant-secret',
                'account_reference' => 'acc-001',
            ])
            ->assertRedirect(route('travel-agents.index', ['tenant' => $tenant->code]));

        $this->assertDatabaseHas('tenant_travel_agent_connections', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $agent->id,
            'status' => 'connected',
            'account_reference' => 'acc-001',
        ]);
    }

    public function test_it_can_test_connection_and_write_sync_log(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        $service = app(TravelAgentConnectionService::class);
        $service->ensureDefaultTravelAgents();
        $agent = TravelAgent::query()->where('code', 'getyourguide')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('travel-agents.test', $agent), [
                'api_key' => 'gyg_valid_key_123456',
                'api_secret' => 'secret',
                'account_reference' => 'supplier-001',
            ])
            ->assertRedirect(route('travel-agents.index', ['tenant' => $tenant->code]));

        $this->assertDatabaseHas('channel_sync_logs', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $agent->id,
            'event_type' => 'connection.tested',
            'status' => 'success',
        ]);
    }

    public function test_non_admin_cannot_access_travel_agents_page(): void
    {
        $guide = User::factory()->create(['role' => 'guide']);

        $this->actingAs($guide)
            ->get(route('travel-agents.index'))
            ->assertRedirect(route('root'));
    }

    public function test_account_reference_is_required_when_connecting_travel_agent(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        $service = app(TravelAgentConnectionService::class);
        $service->ensureDefaultTravelAgents();
        $agent = TravelAgent::query()->where('code', 'getyourguide')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('travel-agents.index', ['tenant' => $tenant->code]))
            ->post(route('travel-agents.connect', $agent), [
                'api_key' => 'tenant-key',
                'api_secret' => 'tenant-secret',
                'account_reference' => '',
            ])
            ->assertRedirect(route('travel-agents.index', ['tenant' => $tenant->code]));

        $this->assertDatabaseMissing('tenant_travel_agent_connections', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $agent->id,
            'status' => 'connected',
        ]);
    }
}
