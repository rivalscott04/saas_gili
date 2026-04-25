<?php

namespace App\Services\TravelAgents\Contracts;

interface TravelAgentConnector
{
    /**
     * @param array{api_key: string, api_secret?: string|null, account_reference?: string|null, __gyg_probe_tenant_id?: int, __gyg_probe_travel_agent_id?: int} $credentials
     * @return array{ok: bool, message: string, context?: array<string, mixed>}
     */
    public function testConnection(array $credentials): array;
}
