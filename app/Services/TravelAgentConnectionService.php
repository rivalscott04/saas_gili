<?php

namespace App\Services;

use App\Models\ChannelSyncLog;
use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;
use App\Models\User;
use App\Services\TravelAgents\TravelAgentConnectorRegistry;
use App\Support\GygPlatformIntegrator;
use App\Support\TenantPicker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TravelAgentConnectionService
{
    public function __construct(private readonly TravelAgentConnectorRegistry $connectorRegistry)
    {
    }

    /**
     * @var list<array{code: string, name: string, signup_url: string, docs_url: string, sort_order: int}>
     */
    private const DEFAULT_TRAVEL_AGENTS = [
        [
            'code' => 'getyourguide',
            'name' => 'GetYourGuide',
            'signup_url' => 'https://supplier.getyourguide.com',
            'docs_url' => 'https://supply.getyourguide.support',
            'sort_order' => 10,
        ],
        [
            'code' => 'viator',
            'name' => 'Viator',
            'signup_url' => 'https://supplier.viator.com',
            'docs_url' => 'https://partnerresources.viator.com',
            'sort_order' => 20,
        ],
        [
            'code' => 'klook',
            'name' => 'Klook',
            'signup_url' => 'https://www.klook.com/partner/',
            'docs_url' => 'https://partner.klook.com',
            'sort_order' => 30,
        ],
        [
            'code' => 'airbnb',
            'name' => 'Airbnb',
            'signup_url' => 'https://www.airbnb.com/partner',
            'docs_url' => 'https://www.airbnb.com/partner/help',
            'sort_order' => 40,
        ],
    ];

    public function ensureDefaultTravelAgents(): void
    {
        if (Cache::get('travel_agents.defaults_seeded_v2')) {
            return;
        }

        foreach (self::DEFAULT_TRAVEL_AGENTS as $agent) {
            TravelAgent::query()->updateOrCreate(
                ['code' => $agent['code']],
                [
                    'name' => $agent['name'],
                    'signup_url' => $agent['signup_url'],
                    'docs_url' => $agent['docs_url'],
                    'is_active' => true,
                    'sort_order' => $agent['sort_order'],
                ]
            );
        }

        Cache::put('travel_agents.defaults_seeded_v2', true, now()->addDay());
    }

    public function resolveTenantForViewer(User $viewer, string|int|null $requestedTenantScope): ?Tenant
    {
        if ($viewer->isSuperAdmin()) {
            $query = Tenant::query()->orderBy('name');
            if (is_string($requestedTenantScope)) {
                $code = trim($requestedTenantScope);
                if ($code !== '') {
                    return $query->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
                }
            } elseif (is_int($requestedTenantScope) && $requestedTenantScope > 0) {
                return $query->whereKey($requestedTenantScope)->first();
            }

            return $query->first();
        }

        if (! $viewer->tenant_id) {
            return null;
        }

        return Tenant::query()->find($viewer->tenant_id);
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function availableTenantsForViewer(User $viewer): Collection
    {
        if (! $viewer->isSuperAdmin()) {
            return collect();
        }

        return TenantPicker::optionsForSuperAdmin();
    }

    /**
     * @return Collection<int, TravelAgent>
     */
    public function listWithTenantConnections(int $tenantId): Collection
    {
        $this->syncPlatformManagedGygConnection($tenantId);

        return TravelAgent::query()
            ->where('is_active', true)
            ->with(['tenantConnections' => function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * When GYG platform integrator credentials are in .env, mark GetYourGuide as connected
     * per tenant using tenant.code as supplierId (no per-tenant API key required).
     */
    public function syncPlatformManagedGygConnection(int $tenantId): void
    {
        if (! GygPlatformIntegrator::isEnabled()) {
            return;
        }

        $tenant = Tenant::query()->find($tenantId);
        if (! $tenant) {
            return;
        }

        $supplierId = GygPlatformIntegrator::supplierIdForTenant($tenant);
        if ($supplierId === '') {
            return;
        }

        $travelAgent = TravelAgent::query()
            ->whereRaw('LOWER(code) = ?', [GygPlatformIntegrator::AGENT_CODE])
            ->first();
        if (! $travelAgent) {
            return;
        }

        $existing = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        if ($existing !== null
            && strtolower((string) $existing->status) === 'connected'
            && ! GygPlatformIntegrator::isPlatformManaged($existing)
            && trim((string) $existing->api_key) !== '') {
            return;
        }

        $wasConnected = $existing !== null && strtolower((string) $existing->status) === 'connected';

        $connection = TenantTravelAgentConnection::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'travel_agent_id' => $travelAgent->id,
            ],
            [
                'status' => 'connected',
                'api_key' => null,
                'api_secret' => null,
                'account_reference' => $supplierId,
                'extra_config' => [
                    GygPlatformIntegrator::EXTRA_PLATFORM_MANAGED => true,
                    GygPlatformIntegrator::EXTRA_SUPPLIER_ID => $supplierId,
                    GygPlatformIntegrator::EXTRA_INTEGRATION_MODE => GygPlatformIntegrator::INTEGRATION_MODE_SUPPLIER_API,
                ],
                'connected_at' => $existing?->connected_at ?? now(),
                'last_checked_at' => now(),
                'last_error' => null,
            ]
        );

        if (! $wasConnected) {
            $this->logSyncEvent($tenantId, (int) $travelAgent->id, 'connection.platform_managed', 'success', __('translation.gyg-platform-managed-connected-log'), [
                'agent_code' => $travelAgent->code,
                'supplier_id' => $supplierId,
                'connection_id' => (int) $connection->id,
            ]);
        }
    }

    /**
     * @return array{blocked: bool, message?: string}
     */
    public function disconnect(int $tenantId, TravelAgent $travelAgent): array
    {
        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        if (! $connection) {
            return ['blocked' => false];
        }

        if (GygPlatformIntegrator::isPlatformManaged($connection)) {
            return [
                'blocked' => true,
                'message' => __('translation.gyg-platform-managed-disconnect-blocked'),
            ];
        }

        $connection->update([
            'status' => 'disconnected',
            'api_key' => null,
            'api_secret' => null,
            'account_reference' => null,
            'extra_config' => null,
            'last_checked_at' => now(),
            'last_error' => null,
        ]);

        $this->logSyncEvent($tenantId, $travelAgent->id, 'connection.disconnected', 'success', 'Koneksi travel agent diputus manual.', [
            'agent_code' => $travelAgent->code,
        ]);

        return ['blocked' => false];
    }

    /**
     * @return array<string, array{label: string, class: string, brand_color: string, image: string|null}>
     */
    public function brandingMap(): array
    {
        /** @var array<string, array{label: string, class: string, brand_color: string, image: string|null}> $map */
        $map = config('travel_agents.branding', []);

        return $map;
    }

    /**
     * @param array{
     *   api_key?: string|null,
     *   api_secret?: string|null,
     *   account_reference?: string|null,
     *   supplier_basic_username?: string|null,
     *   supplier_basic_password?: string|null,
     *   supplier_id?: string|null
     * } $payload
     */
    public function upsertConnection(int $tenantId, TravelAgent $travelAgent, array $payload): TenantTravelAgentConnection
    {
        $existing = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();
        $existingExtraConfig = is_array($existing?->extra_config) ? $existing->extra_config : [];
        unset(
            $existingExtraConfig[GygPlatformIntegrator::EXTRA_PLATFORM_MANAGED],
            $existingExtraConfig[GygPlatformIntegrator::EXTRA_INTEGRATION_MODE]
        );
        $tenantCode = (string) Tenant::query()->whereKey($tenantId)->value('code');
        $extraConfig = array_merge($existingExtraConfig, [
            'supplier_basic_username' => trim((string) ($payload['supplier_basic_username'] ?? '')),
            'supplier_basic_password' => trim((string) ($payload['supplier_basic_password'] ?? '')),
            'supplier_id' => trim((string) ($payload['supplier_id'] ?? '')) !== ''
                ? trim((string) $payload['supplier_id'])
                : ($tenantCode !== '' ? $tenantCode : trim((string) ($payload['account_reference'] ?? ''))),
        ]);

        $connection = TenantTravelAgentConnection::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'travel_agent_id' => $travelAgent->id,
            ],
            [
                'status' => 'connected',
                'api_key' => $payload['api_key'] ?? null,
                'api_secret' => $payload['api_secret'] ?? null,
                'account_reference' => $payload['account_reference'] ?? null,
                'extra_config' => $extraConfig,
                'connected_at' => now(),
                'last_checked_at' => now(),
                'last_error' => null,
            ]
        );

        $this->logSyncEvent($tenantId, $travelAgent->id, 'connection.connected', 'success', 'Credential travel agent disimpan.', [
            'agent_code' => $travelAgent->code,
        ]);

        return $connection;
    }

    /**
     * @param  array{api_key: string, api_secret?: string|null, account_reference?: string|null}  $payload
     * @return array{ok: bool, message: string}
     */
    public function testConnection(int $tenantId, TravelAgent $travelAgent, array $payload): array
    {
        $existing = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        $apiKey = trim((string) ($payload['api_key'] ?? ''));
        if ($apiKey === '' && GygPlatformIntegrator::isPlatformManaged($existing)) {
            $message = __('translation.gyg-platform-managed-test-ok');

            $this->logSyncEvent($tenantId, $travelAgent->id, 'connection.tested', 'success', $message, [
                'agent_code' => $travelAgent->code,
                'platform_managed' => true,
            ]);

            return ['ok' => true, 'message' => $message];
        }

        if ($apiKey === '' && GygPlatformIntegrator::isEnabled() && GygPlatformIntegrator::isGetYourGuideAgent($travelAgent)) {
            $this->syncPlatformManagedGygConnection($tenantId);
            $message = __('translation.gyg-platform-managed-test-ok');

            $this->logSyncEvent($tenantId, $travelAgent->id, 'connection.tested', 'success', $message, [
                'agent_code' => $travelAgent->code,
                'platform_managed' => true,
            ]);

            return ['ok' => true, 'message' => $message];
        }

        $probeContext = [
            '__gyg_probe_tenant_id' => $tenantId,
            '__gyg_probe_travel_agent_id' => (int) $travelAgent->id,
        ];
        $result = $this->connectorRegistry->forAgent($travelAgent)->testConnection(array_merge($payload, $probeContext));
        $ok = (bool) ($result['ok'] ?? false);
        $message = (string) ($result['message'] ?? ($ok ? 'OK' : 'Failed'));
        $status = $ok ? 'success' : 'error';

        $this->logSyncEvent($tenantId, $travelAgent->id, 'connection.tested', $status, $message, [
            'agent_code' => $travelAgent->code,
            'context' => $result['context'] ?? [],
        ]);

        return [
            'ok' => $ok,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logSyncEvent(
        int $tenantId,
        int $travelAgentId,
        string $eventType,
        string $status,
        string $message,
        array $context = []
    ): void {
        ChannelSyncLog::query()->create([
            'tenant_id' => $tenantId,
            'travel_agent_id' => $travelAgentId,
            'event_type' => $eventType,
            'direction' => 'internal',
            'status' => $status,
            'message' => $message,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
