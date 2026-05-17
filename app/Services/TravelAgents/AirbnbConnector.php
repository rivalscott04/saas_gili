<?php

namespace App\Services\TravelAgents;

use App\Models\TenantTravelAgentConnection;
use App\Services\TravelAgents\Contracts\TravelAgentConnector;
use App\Support\AirbnbPlatformIntegrator;

class AirbnbConnector implements TravelAgentConnector
{
    public function testConnection(array $credentials): array
    {
        $tenantId = (int) ($credentials['__airbnb_probe_tenant_id'] ?? 0);
        $travelAgentId = (int) ($credentials['__airbnb_probe_travel_agent_id'] ?? 0);

        if ($tenantId <= 0 || $travelAgentId <= 0) {
            return [
                'ok' => false,
                'message' => __('translation.airbnb-test-missing-context'),
            ];
        }

        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgentId)
            ->first();

        if (! $connection || ! AirbnbPlatformIntegrator::usesOAuth($connection)) {
            return [
                'ok' => false,
                'message' => __('translation.airbnb-oauth-not-connected'),
            ];
        }

        $result = (new AirbnbClient($connection))->testConnection();

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'context' => [
                'provider' => AirbnbPlatformIntegrator::AGENT_CODE,
                'http_status' => $result['status'] ?? 0,
            ],
        ];
    }
}
