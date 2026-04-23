<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TravelAgent;
use App\Services\TravelAgentConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelAgentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_getyourguide_webhook_logs_received_when_signature_is_valid(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'tenant-a']);
        $service = app(TravelAgentConnectionService::class);
        $service->ensureDefaultTravelAgents();
        $agent = TravelAgent::query()->where('code', 'getyourguide')->firstOrFail();
        $service->upsertConnection((int) $tenant->id, $agent, [
            'api_key' => 'gyg_key_123456',
            'api_secret' => 'super-secret',
            'account_reference' => 'supplier-1',
        ]);

        $body = json_encode(['event' => 'booking.created', 'booking_id' => 'GYG-1001'], JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', (string) $body, 'super-secret');

        $this->call(
            'POST',
            '/api/v1/webhooks/travel-agents/getyourguide',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TENANT_CODE' => 'tenant-a',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ],
            (string) $body
        )->assertStatus(202);

        $this->assertDatabaseHas('channel_sync_logs', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $agent->id,
            'event_type' => 'webhook.received',
            'status' => 'success',
        ]);
    }

    public function test_getyourguide_webhook_rejects_invalid_signature(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'tenant-b']);
        $service = app(TravelAgentConnectionService::class);
        $service->ensureDefaultTravelAgents();
        $agent = TravelAgent::query()->where('code', 'getyourguide')->firstOrFail();
        $service->upsertConnection((int) $tenant->id, $agent, [
            'api_key' => 'gyg_key_123456',
            'api_secret' => 'super-secret',
            'account_reference' => 'supplier-1',
        ]);

        $this->postJson('/api/v1/webhooks/travel-agents/getyourguide', [
            'event' => 'booking.created',
            'booking_id' => 'GYG-1002',
        ], [
            'X-Tenant-Code' => 'tenant-b',
            'X-Webhook-Signature' => 'invalid-signature',
        ])->assertStatus(401);

        $this->assertDatabaseHas('channel_sync_logs', [
            'tenant_id' => $tenant->id,
            'travel_agent_id' => $agent->id,
            'event_type' => 'webhook.rejected',
            'status' => 'error',
        ]);
    }
}
