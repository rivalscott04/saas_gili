<?php

namespace App\Services\TravelAgents;

use App\Models\ChannelSyncLog;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;
use App\Support\AirbnbPlatformIntegrator;

class AirbnbBookingSyncService
{
    /**
     * @return array{ok: bool, message: string, imported: int}
     */
    public function pullBookings(
        int $tenantId,
        string $dateFrom,
        string $dateTo,
        bool $forceRepull = false,
        ?int $requestedBy = null
    ): array {
        $travelAgent = TravelAgent::query()
            ->whereRaw('LOWER(code) = ?', [AirbnbPlatformIntegrator::AGENT_CODE])
            ->first();

        if (! $travelAgent) {
            return ['ok' => false, 'message' => 'Airbnb travel agent not found.', 'imported' => 0];
        }

        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        if (! $connection || ! AirbnbPlatformIntegrator::usesOAuth($connection)) {
            $this->log($tenantId, (int) $travelAgent->id, 'pull.failed', 'error', __('translation.airbnb-oauth-not-connected'), [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'force_repull' => $forceRepull,
                'requested_by' => $requestedBy,
            ]);

            return ['ok' => false, 'message' => __('translation.airbnb-oauth-not-connected'), 'imported' => 0];
        }

        $client = new AirbnbClient($connection);
        $result = $client->listReservations($dateFrom, $dateTo);

        if (! ($result['ok'] ?? false)) {
            $connection->update([
                'status' => 'error',
                'last_checked_at' => now(),
                'last_error' => (string) ($result['message'] ?? 'Pull failed'),
            ]);

            $this->log($tenantId, (int) $travelAgent->id, 'pull.failed', 'error', (string) $result['message'], [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'force_repull' => $forceRepull,
                'requested_by' => $requestedBy,
                'http_status' => $result['status'] ?? 0,
            ]);

            return ['ok' => false, 'message' => (string) $result['message'], 'imported' => 0];
        }

        $payload = is_array($result['data']) ? $result['data'] : [];
        $reservations = $payload['reservations'] ?? $payload['data'] ?? $payload;
        $count = is_array($reservations) ? count($reservations) : 0;

        // TODO(airbnb-import): map reservation payloads into local Booking records.
        $connection->update([
            'status' => 'connected',
            'last_checked_at' => now(),
            'last_error' => null,
        ]);

        $this->log($tenantId, (int) $travelAgent->id, 'pull.completed', 'success', __('translation.airbnb-pull-completed', ['count' => $count]), [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'force_repull' => $forceRepull,
            'requested_by' => $requestedBy,
            'fetched' => $count,
        ]);

        return [
            'ok' => true,
            'message' => __('translation.airbnb-pull-completed', ['count' => $count]),
            'imported' => $count,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(
        int $tenantId,
        int $travelAgentId,
        string $eventType,
        string $status,
        string $message,
        array $context = []
    ): void {
        ChannelSyncLog::query()->create([
            'tenant_id' => $tenantId,
            'travel_agent_id' => $travelAgentId,
            'event_type' => $eventType,
            'direction' => 'inbound',
            'status' => $status,
            'message' => $message,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
