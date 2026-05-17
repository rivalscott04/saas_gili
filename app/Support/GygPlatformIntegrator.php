<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;

class GygPlatformIntegrator
{
    public const AGENT_CODE = 'getyourguide';

    public const EXTRA_PLATFORM_MANAGED = 'platform_managed';

    public const EXTRA_SUPPLIER_ID = 'supplier_id';

    public const EXTRA_INTEGRATION_MODE = 'integration_mode';

    public const INTEGRATION_MODE_SUPPLIER_API = 'supplier_api';

    public static function isEnabled(): bool
    {
        $username = trim((string) config('gyg_supplier_api.platform_username', ''));
        $password = trim((string) config('gyg_supplier_api.platform_password', ''));

        return $username !== '' && $password !== '';
    }

    public static function supplierIdForTenant(Tenant $tenant): string
    {
        return trim((string) $tenant->code);
    }

    public static function isGetYourGuideAgent(TravelAgent $travelAgent): bool
    {
        return strtolower((string) $travelAgent->code) === self::AGENT_CODE;
    }

    public static function isPlatformManaged(?TenantTravelAgentConnection $connection): bool
    {
        if ($connection === null || strtolower((string) $connection->status) !== 'connected') {
            return false;
        }

        $extra = is_array($connection->extra_config) ? $connection->extra_config : [];

        return (bool) ($extra[self::EXTRA_PLATFORM_MANAGED] ?? false);
    }

    public static function supplierIdFromConnection(?TenantTravelAgentConnection $connection, Tenant $tenant): string
    {
        if ($connection !== null) {
            $extra = is_array($connection->extra_config) ? $connection->extra_config : [];
            $fromExtra = trim((string) ($extra[self::EXTRA_SUPPLIER_ID] ?? ''));
            if ($fromExtra !== '') {
                return $fromExtra;
            }

            $fromAccount = trim((string) ($connection->account_reference ?? ''));
            if ($fromAccount !== '') {
                return $fromAccount;
            }
        }

        return self::supplierIdForTenant($tenant);
    }

    public static function usesInboundSupplierApi(?TenantTravelAgentConnection $connection): bool
    {
        return self::isPlatformManaged($connection);
    }
}
