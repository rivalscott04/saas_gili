<?php

namespace App\Services;

use App\Models\ChannelSyncLog;
use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;
use App\Models\User;
use App\Services\TravelAgents\TravelAgentConnectorRegistry;
use Illuminate\Support\Collection;

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
    ];

    public function ensureDefaultTravelAgents(): void
    {
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

        return Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    /**
     * @return Collection<int, TravelAgent>
     */
    public function listWithTenantConnections(int $tenantId): Collection
    {
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
     * @return array<string, array{label: string, class: string, brand_color: string, image: string|null}>
     */
    public function brandingMap(): array
    {
        /** @var array<string, array{label: string, class: string, brand_color: string, image: string|null}> $map */
        $map = config('travel_agents.branding', []);

        return $map;
    }

    /**
     * @param array{api_key: string, api_secret?: string|null, account_reference?: string|null} $payload
     */
    public function upsertConnection(int $tenantId, TravelAgent $travelAgent, array $payload): TenantTravelAgentConnection
    {
        $connection = TenantTravelAgentConnection::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'travel_agent_id' => $travelAgent->id,
            ],
            [
                'status' => 'connected',
                'api_key' => $payload['api_key'],
                'api_secret' => $payload['api_secret'] ?? null,
                'account_reference' => $payload['account_reference'] ?? null,
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

    public function disconnect(int $tenantId, TravelAgent $travelAgent): void
    {
        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        if (! $connection) {
            return;
        }

        $connection->update([
            'status' => 'disconnected',
            'api_key' => null,
            'api_secret' => null,
            'account_reference' => null,
            'last_checked_at' => now(),
            'last_error' => null,
        ]);

        $this->logSyncEvent($tenantId, $travelAgent->id, 'connection.disconnected', 'success', 'Koneksi travel agent diputus manual.', [
            'agent_code' => $travelAgent->code,
        ]);
    }

    /**
     * @param array{api_key: string, api_secret?: string|null, account_reference?: string|null} $payload
     * @return array{ok: bool, message: string}
     */
    public function testConnection(int $tenantId, TravelAgent $travelAgent, array $payload): array
    {
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
     * @param array<string, mixed> $context
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
