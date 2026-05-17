<?php

namespace App\Services\TravelAgents;

use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;
use App\Support\AirbnbPlatformIntegrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AirbnbOAuthService
{
    public function authorizationUrl(int $tenantId, int $travelAgentId, int $userId): string
    {
        $state = Str::random(40);
        Cache::put($this->stateCacheKey($state), [
            'tenant_id' => $tenantId,
            'travel_agent_id' => $travelAgentId,
            'user_id' => $userId,
        ], now()->addMinutes(15));

        $query = http_build_query([
            'client_id' => config('airbnb.client_id'),
            'redirect_uri' => config('airbnb.redirect_uri'),
            'response_type' => 'code',
            'scope' => (string) config('airbnb.scopes'),
            'state' => $state,
        ]);

        return rtrim((string) config('airbnb.authorize_url'), '?').'?'.$query;
    }

    /**
     * @return array{ok: bool, message: string, tenant?: Tenant}
     */
    public function handleCallback(string $code, string $state): array
    {
        $cached = Cache::pull($this->stateCacheKey($state));
        if (! is_array($cached)) {
            return ['ok' => false, 'message' => __('translation.airbnb-oauth-state-invalid')];
        }

        $tenant = Tenant::query()->find((int) ($cached['tenant_id'] ?? 0));
        $travelAgent = TravelAgent::query()->find((int) ($cached['travel_agent_id'] ?? 0));
        if (! $tenant || ! $travelAgent || ! AirbnbPlatformIntegrator::isAirbnbAgent($travelAgent)) {
            return ['ok' => false, 'message' => __('translation.airbnb-oauth-tenant-invalid')];
        }

        $tokenResponse = Http::asForm()
            ->timeout((int) config('airbnb.timeout_seconds', 30))
            ->post((string) config('airbnb.token_url'), [
                'grant_type' => 'authorization_code',
                'client_id' => config('airbnb.client_id'),
                'client_secret' => config('airbnb.client_secret'),
                'code' => $code,
                'redirect_uri' => config('airbnb.redirect_uri'),
            ]);

        if (! $tokenResponse->successful()) {
            return [
                'ok' => false,
                'message' => __('translation.airbnb-oauth-token-failed', [
                    'detail' => Str::limit((string) $tokenResponse->body(), 200),
                ]),
            ];
        }

        $body = $tokenResponse->json();
        if (! is_array($body)) {
            return ['ok' => false, 'message' => __('translation.airbnb-oauth-token-invalid')];
        }

        $accessToken = trim((string) ($body['access_token'] ?? ''));
        if ($accessToken === '') {
            return ['ok' => false, 'message' => __('translation.airbnb-oauth-token-invalid')];
        }

        $refreshToken = trim((string) ($body['refresh_token'] ?? ''));
        $expiresIn = (int) ($body['expires_in'] ?? 0);
        $hostUserId = trim((string) ($body['user_id'] ?? $body['host_id'] ?? ''));

        TenantTravelAgentConnection::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'travel_agent_id' => $travelAgent->id,
            ],
            [
                'status' => 'connected',
                'api_key' => $accessToken,
                'api_secret' => $refreshToken !== '' ? $refreshToken : null,
                'account_reference' => $hostUserId !== '' ? $hostUserId : null,
                'extra_config' => [
                    AirbnbPlatformIntegrator::EXTRA_OAUTH_CONNECTED_AT => now()->toIso8601String(),
                    AirbnbPlatformIntegrator::EXTRA_OAUTH_EXPIRES_AT => $expiresIn > 0
                        ? now()->addSeconds($expiresIn)->toIso8601String()
                        : null,
                    AirbnbPlatformIntegrator::EXTRA_HOST_USER_ID => $hostUserId !== '' ? $hostUserId : null,
                ],
                'connected_at' => now(),
                'last_checked_at' => now(),
                'last_error' => null,
            ]
        );

        return [
            'ok' => true,
            'message' => __('translation.airbnb-oauth-connected'),
            'tenant' => $tenant,
        ];
    }

    private function stateCacheKey(string $state): string
    {
        return 'airbnb_oauth_state:'.hash('sha256', $state);
    }
}
