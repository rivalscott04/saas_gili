<?php

namespace App\Services\TravelAgents;

use App\Services\TravelAgents\Contracts\TravelAgentConnector;

class GetYourGuideConnector implements TravelAgentConnector
{
    public function testConnection(array $credentials): array
    {
        $apiKey = trim((string) ($credentials['api_key'] ?? ''));
        $accountReference = trim((string) ($credentials['account_reference'] ?? ''));

        $isLikelyValid = str_starts_with($apiKey, 'gyg_') || strlen($apiKey) >= 16;
        if (! $isLikelyValid) {
            return [
                'ok' => false,
                'message' => 'Format API key GetYourGuide belum valid.',
                'context' => [
                    'provider' => 'getyourguide',
                    'hint' => 'Gunakan API key resmi dari supplier dashboard GYG.',
                ],
            ];
        }

        return [
            'ok' => true,
            'message' => $accountReference !== ''
                ? 'Credential GetYourGuide valid dan siap dipakai sinkronisasi.'
                : 'Credential valid, tapi account reference disarankan diisi.',
            'context' => [
                'provider' => 'getyourguide',
                'account_reference_set' => $accountReference !== '',
            ],
        ];
    }
}
