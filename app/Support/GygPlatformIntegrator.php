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

    /**
     * True when any Supplier API integrator credentials exist in .env
     * (platform user, legacy single-supplier, or JSON credentials list).
     */
    public static function isEnabled(): bool
    {
        return self::hasPlatformCredentials()
            || self::hasLegacySingleSupplierCredentials()
            || self::configuredCredentials() !== [];
    }

    public static function usesMultiTenantPlatformCredentials(): bool
    {
        return self::hasPlatformCredentials();
    }

    public static function supplierIdForTenant(Tenant $tenant): string
    {
        return trim((string) $tenant->code);
    }

    public static function isGetYourGuideAgent(TravelAgent $travelAgent): bool
    {
        return strtolower((string) $travelAgent->code) === self::AGENT_CODE;
    }

    /**
     * Whether this tenant should auto-link to GetYourGuide via server .env (no per-tenant API key).
     */
    public static function tenantIsAutoConnectEligible(Tenant $tenant): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $tenantCode = strtolower(trim((string) $tenant->code));
        if ($tenantCode === '') {
            return false;
        }

        if (self::hasPlatformCredentials()) {
            return true;
        }

        $legacySupplierId = strtolower(trim((string) config('gyg_supplier_api.supplier_id', '')));
        if (self::hasLegacySingleSupplierCredentials() && $legacySupplierId !== '' && $legacySupplierId === $tenantCode) {
            return true;
        }

        foreach (self::configuredCredentials() as $credential) {
            $supplierId = strtolower(trim((string) ($credential['supplier_id'] ?? '')));
            if ($supplierId !== '' && $supplierId === $tenantCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hide Sign Up / Docs / manual Manage for tenants covered by integrator .env.
     */
    public static function shouldHideResellerSelfServiceUi(TravelAgent $travelAgent, ?TenantTravelAgentConnection $connection, Tenant $tenant): bool
    {
        if (! self::isGetYourGuideAgent($travelAgent)) {
            return ! self::isResellerChannelImplemented($travelAgent);
        }

        if (self::isPlatformManaged($connection)) {
            return true;
        }

        return self::tenantIsAutoConnectEligible($tenant);
    }

    public static function isResellerChannelImplemented(TravelAgent $travelAgent): bool
    {
        return self::isGetYourGuideAgent($travelAgent);
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

    private static function hasPlatformCredentials(): bool
    {
        $username = trim((string) config('gyg_supplier_api.platform_username', ''));
        $password = trim((string) config('gyg_supplier_api.platform_password', ''));

        return $username !== '' && $password !== '';
    }

    private static function hasLegacySingleSupplierCredentials(): bool
    {
        $username = trim((string) config('gyg_supplier_api.username', ''));
        $password = trim((string) config('gyg_supplier_api.password', ''));

        return $username !== '' && $password !== '';
    }

    /**
     * @return list<array{username: string, password: string, supplier_id: string}>
     */
    private static function configuredCredentials(): array
    {
        /** @var list<array{username?: string, password?: string, supplier_id?: string}> $rows */
        $rows = (array) config('gyg_supplier_api.credentials', []);
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $username = trim((string) ($row['username'] ?? ''));
            $password = trim((string) ($row['password'] ?? ''));
            $supplierId = trim((string) ($row['supplier_id'] ?? ''));
            if ($username === '' || $password === '' || $supplierId === '') {
                continue;
            }
            $out[] = [
                'username' => $username,
                'password' => $password,
                'supplier_id' => $supplierId,
            ];
        }

        return $out;
    }
}
