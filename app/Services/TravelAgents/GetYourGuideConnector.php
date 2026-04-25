<?php

namespace App\Services\TravelAgents;

use App\Services\TravelAgents\Contracts\TravelAgentConnector;

class GetYourGuideConnector implements TravelAgentConnector
{
    private const CONTEXT_TENANT_KEY = '__gyg_probe_tenant_id';

    private const CONTEXT_AGENT_KEY = '__gyg_probe_travel_agent_id';

    public function testConnection(array $credentials): array
    {
        $tenantId = (int) ($credentials[self::CONTEXT_TENANT_KEY] ?? 0);
        $travelAgentId = (int) ($credentials[self::CONTEXT_AGENT_KEY] ?? 0);
        unset($credentials[self::CONTEXT_TENANT_KEY], $credentials[self::CONTEXT_AGENT_KEY]);

        $apiKey = trim((string) ($credentials['api_key'] ?? ''));
        $apiSecret = isset($credentials['api_secret']) ? trim((string) $credentials['api_secret']) : '';
        $apiSecret = $apiSecret !== '' ? $apiSecret : null;
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

        if ($tenantId <= 0 || $travelAgentId <= 0) {
            return [
                'ok' => false,
                'message' => 'Tes koneksi tidak dapat dijalankan (konteks tenant hilang).',
                'context' => ['provider' => 'getyourguide'],
            ];
        }

        $client = new GetYourGuideClient($apiKey, $apiSecret, $tenantId, $travelAgentId);
        $probe = $client->probeConnection();
        $ok = (bool) ($probe['ok'] ?? false);
        $message = (string) ($probe['message'] ?? ($ok ? 'OK' : 'Gagal'));
        if ($ok && $accountReference === '') {
            $message .= ' Disarankan mengisi account reference untuk produksi.';
        }

        return [
            'ok' => $ok,
            'message' => $message,
            'context' => array_merge(
                ['provider' => 'getyourguide', 'account_reference_set' => $accountReference !== ''],
                is_array($probe['context'] ?? null) ? $probe['context'] : []
            ),
        ];
    }
}
