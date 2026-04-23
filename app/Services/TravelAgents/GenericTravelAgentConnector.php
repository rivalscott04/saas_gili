<?php

namespace App\Services\TravelAgents;

use App\Services\TravelAgents\Contracts\TravelAgentConnector;

class GenericTravelAgentConnector implements TravelAgentConnector
{
    public function testConnection(array $credentials): array
    {
        $apiKey = trim((string) ($credentials['api_key'] ?? ''));
        if ($apiKey === '') {
            return [
                'ok' => false,
                'message' => 'API key wajib diisi.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'Credential tersimpan. Connector khusus agent ini masih tahap berikutnya.',
            'context' => [
                'mode' => 'manual-validation',
            ],
        ];
    }
}
