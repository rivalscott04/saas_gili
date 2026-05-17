<?php

namespace App\Support;

use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;

class AirbnbPlatformIntegrator
{
    public const AGENT_CODE = 'airbnb';

    public const EXTRA_OAUTH_CONNECTED_AT = 'oauth_connected_at';

    public const EXTRA_OAUTH_EXPIRES_AT = 'oauth_expires_at';

    public const EXTRA_HOST_USER_ID = 'host_user_id';

    public static function isEnabled(): bool
    {
        $clientId = trim((string) config('airbnb.client_id', ''));
        $clientSecret = trim((string) config('airbnb.client_secret', ''));
        $redirectUri = trim((string) config('airbnb.redirect_uri', ''));

        return $clientId !== '' && $clientSecret !== '' && $redirectUri !== '';
    }

    public static function isAirbnbAgent(TravelAgent $travelAgent): bool
    {
        return strtolower((string) $travelAgent->code) === self::AGENT_CODE;
    }

    public static function usesOAuth(?TenantTravelAgentConnection $connection): bool
    {
        if ($connection === null || strtolower((string) $connection->status) !== 'connected') {
            return false;
        }

        return trim((string) $connection->api_key) !== '';
    }

    public static function hostUserId(?TenantTravelAgentConnection $connection): ?string
    {
        if ($connection === null) {
            return null;
        }

        $extra = is_array($connection->extra_config) ? $connection->extra_config : [];
        $fromExtra = trim((string) ($extra[self::EXTRA_HOST_USER_ID] ?? ''));
        if ($fromExtra !== '') {
            return $fromExtra;
        }

        $fromAccount = trim((string) ($connection->account_reference ?? ''));

        return $fromAccount !== '' ? $fromAccount : null;
    }
}
