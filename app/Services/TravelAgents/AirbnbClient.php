<?php

namespace App\Services\TravelAgents;

use App\Models\TenantTravelAgentConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AirbnbClient
{
    public function __construct(private readonly TenantTravelAgentConnection $connection)
    {
    }

    public function hasAccessToken(): bool
    {
        return trim((string) $this->connection->api_key) !== '';
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->hasAccessToken()) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => null,
                'message' => __('translation.airbnb-oauth-not-connected'),
            ];
        }

        $response = $this->request()->get('/users/me');

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $response->status(),
                'data' => is_array($response->json()) ? $response->json() : null,
                'message' => __('translation.airbnb-test-ok'),
            ];
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'data' => null,
            'message' => __('translation.airbnb-test-failed', [
                'status' => $response->status(),
                'detail' => Str::limit((string) $response->body(), 200),
            ]),
        ];
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, message: string}
     */
    public function listReservations(string $dateFrom, string $dateTo): array
    {
        if (! $this->hasAccessToken()) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => null,
                'message' => __('translation.airbnb-oauth-not-connected'),
            ];
        }

        $response = $this->request()->get('/reservations', [
            'start_date' => $dateFrom,
            'end_date' => $dateTo,
        ]);

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $response->status(),
                'data' => is_array($response->json()) ? $response->json() : [],
                'message' => __('translation.airbnb-pull-ok'),
            ];
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'data' => null,
            'message' => __('translation.airbnb-pull-failed', [
                'status' => $response->status(),
                'detail' => Str::limit((string) $response->body(), 200),
            ]),
        ];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('airbnb.api_base_url'), '/'))
            ->timeout((int) config('airbnb.timeout_seconds', 30))
            ->withToken(trim((string) $this->connection->api_key))
            ->acceptJson();
    }
}
