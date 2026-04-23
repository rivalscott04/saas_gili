<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\TravelAgent;
use App\Services\TravelAgentConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelAgentConnectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_default_travel_agents_and_manages_connection_status(): void
    {
        $tenant = Tenant::factory()->create();
        $service = app(TravelAgentConnectionService::class);

        $service->ensureDefaultTravelAgents();

        $this->assertDatabaseHas('travel_agents', ['code' => 'getyourguide']);
        $this->assertDatabaseHas('travel_agents', ['code' => 'viator']);
        $this->assertDatabaseHas('travel_agents', ['code' => 'klook']);

        $gyg = TravelAgent::query()->where('code', 'getyourguide')->firstOrFail();
        $service->upsertConnection((int) $tenant->id, $gyg, [
            'api_key' => 'key-123',
            'api_secret' => 'secret-abc',
            'account_reference' => 'supplier-55',
        ]);

        $this->assertDatabaseHas('tenant_travel_agent_connections', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $gyg->id,
            'status' => 'connected',
            'account_reference' => 'supplier-55',
        ]);

        $service->disconnect((int) $tenant->id, $gyg);
        $this->assertDatabaseHas('tenant_travel_agent_connections', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $gyg->id,
            'status' => 'disconnected',
        ]);
    }
}
