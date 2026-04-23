<?php

namespace App\Services\TravelAgents;

use App\Models\ChannelSyncLog;
use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;

class TravelAgentWebhookService
{
    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, status: int, message: string}
     */
    public function ingestGetYourGuideWebhook(string $tenantCode, ?string $signature, string $rawBody, array $payload): array
    {
        $travelAgent = TravelAgent::query()->where('code', 'getyourguide')->first();
        $tenant = Tenant::query()->where('code', $tenantCode)->first();

        if (! $travelAgent || ! $tenant) {
            $this->log(null, null, 'webhook.rejected', 'error', 'Tenant/agent tidak valid.', $payload, [
                'tenant_code' => $tenantCode,
            ]);

            return ['ok' => false, 'status' => 404, 'message' => 'Tenant or agent not found'];
        }

        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenant->id)
            ->where('travel_agent_id', $travelAgent->id)
            ->where('status', 'connected')
            ->first();

        if (! $connection) {
            $this->log($tenant->id, $travelAgent->id, 'webhook.rejected', 'error', 'Koneksi tenant-agent belum aktif.', $payload);

            return ['ok' => false, 'status' => 403, 'message' => 'Connection is not active'];
        }

        $secret = (string) ($connection->api_secret ?? '');
        if ($secret === '' || ! $this->isValidSignature($secret, (string) $signature, $rawBody)) {
            $this->log($tenant->id, $travelAgent->id, 'webhook.rejected', 'error', 'Signature webhook tidak valid.', $payload);

            return ['ok' => false, 'status' => 401, 'message' => 'Invalid signature'];
        }

        $this->log($tenant->id, $travelAgent->id, 'webhook.received', 'success', 'Webhook diterima (safe mode).', $payload);
        $this->log($tenant->id, $travelAgent->id, 'webhook.queued', 'success', 'Webhook ditandai queued untuk proses berikutnya.', [
            'received_payload_size' => strlen($rawBody),
            'received_at' => now()->toIso8601String(),
        ]);

        return ['ok' => true, 'status' => 202, 'message' => 'Accepted'];
    }

    private function isValidSignature(string $secret, string $signature, string $rawBody): bool
    {
        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    private function log(
        ?int $tenantId,
        ?int $travelAgentId,
        string $eventType,
        string $status,
        string $message,
        array $payload = [],
        array $context = []
    ): void {
        ChannelSyncLog::query()->create([
            'tenant_id' => $tenantId,
            'travel_agent_id' => $travelAgentId,
            'event_type' => $eventType,
            'direction' => 'inbound',
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
