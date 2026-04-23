<?php

namespace App\Services\TravelAgents\Contracts;

interface TravelAgentConnector
{
    /**
     * @param array{api_key: string, api_secret?: string|null, account_reference?: string|null} $credentials
     * @return array{ok: bool, message: string, context?: array<string, mixed>}
     */
    public function testConnection(array $credentials): array;
}
