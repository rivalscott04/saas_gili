<?php

namespace App\Http\Controllers;

use App\Models\ChannelSyncLog;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;
use App\Services\TravelAgentConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ChannelSyncController extends Controller
{
    public function __construct(private readonly TravelAgentConnectionService $connectionService)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('viewAny', TravelAgent::class)) {
            return redirect()->route('root');
        }

        $this->connectionService->ensureDefaultTravelAgents();
        $tenant = $this->connectionService->resolveTenantForViewer(
            $viewer,
            $viewer->isSuperAdmin() ? trim((string) $request->query('tenant', '')) : null
        );
        if (! $tenant) {
            return redirect()->route('root');
        }

        $tenantId = (int) $tenant->id;
        $since = Carbon::now()->subDays(7);

        $pullAggregates = ChannelSyncLog::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since)
            ->selectRaw(
                "SUM(CASE WHEN event_type LIKE 'pull.%' AND status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN event_type LIKE 'pull.%' AND status = 'error' THEN 1 ELSE 0 END) as error,
                SUM(CASE WHEN event_type = 'pull.requested' THEN 1 ELSE 0 END) as queued"
            )
            ->first();

        $lastRunAt = ChannelSyncLog::query()
            ->where('tenant_id', $tenantId)
            ->where('event_type', 'like', 'pull.%')
            ->orderByDesc('id')
            ->selectRaw('COALESCE(occurred_at, created_at) as last_run_at')
            ->value('last_run_at');

        $stats = [
            'window_days' => 7,
            'success' => (int) ($pullAggregates->success ?? 0),
            'error' => (int) ($pullAggregates->error ?? 0),
            'queued' => (int) ($pullAggregates->queued ?? 0),
            'last_run_at' => $lastRunAt,
        ];

        $travelAgents = $this->connectionService->listWithTenantConnections($tenantId);

        $lastRunsByAgent = ChannelSyncLog::query()
            ->where('tenant_id', $tenantId)
            ->where('event_type', 'like', 'pull.%')
            ->orderByDesc('id')
            ->limit(120)
            ->get(['id', 'travel_agent_id', 'event_type', 'status', 'message', 'occurred_at', 'created_at'])
            ->groupBy('travel_agent_id')
            ->map(fn ($items) => $items->first());

        $recentRuns = ChannelSyncLog::query()
            ->with('travelAgent:id,name,code')
            ->where('tenant_id', $tenantId)
            ->where('event_type', 'like', 'pull.%')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('apps-channel-sync', [
            'tenant' => $tenant,
            'travelAgents' => $travelAgents,
            'brandingMap' => $this->connectionService->brandingMap(),
            'availableTenants' => $this->connectionService->availableTenantsForViewer($viewer),
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
            'stats' => $stats,
            'lastRunsByAgent' => $lastRunsByAgent,
            'recentRuns' => $recentRuns,
            'canTriggerSync' => $viewer->hasPlatformPermission('platform.travel_agents.sync'),
            'canViewSyncLogs' => $viewer->can('viewAny', ChannelSyncLog::class),
        ]);
    }

    public function pull(Request $request, TravelAgent $travelAgent): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('viewAny', TravelAgent::class)) {
            return redirect()->route('root');
        }

        if (! $viewer->hasPlatformPermission('platform.travel_agents.sync')) {
            return back()->with('system_alert', [
                'icon' => 'warning',
                'title' => __('translation.channel-sync'),
                'message' => __('translation.channel-sync-no-permission'),
            ]);
        }

        $tenant = $this->connectionService->resolveTenantForViewer(
            $viewer,
            $viewer->isSuperAdmin() ? trim((string) $request->input('tenant_code', '')) : null
        );
        if (! $tenant) {
            return redirect()->route('root');
        }

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'force_repull' => ['nullable', 'boolean'],
        ]);

        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenant->id)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        if (! $connection || strtolower((string) $connection->status) !== 'connected') {
            return back()->with('system_alert', [
                'icon' => 'warning',
                'title' => $travelAgent->name,
                'message' => __('translation.channel-sync-no-connection-for-agent'),
            ]);
        }

        $dateFrom = ! empty($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->toDateString()
            : Carbon::today()->toDateString();
        $dateTo = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->toDateString()
            : Carbon::today()->addDays(30)->toDateString();

        ChannelSyncLog::query()->create([
            'tenant_id' => (int) $tenant->id,
            'travel_agent_id' => (int) $travelAgent->id,
            'event_type' => 'pull.requested',
            'direction' => 'inbound',
            'status' => 'success',
            'message' => __('translation.channel-sync-pull-success-message'),
            'context' => [
                'agent_code' => $travelAgent->code,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'force_repull' => (bool) ($validated['force_repull'] ?? false),
                'requested_by' => (int) $viewer->id,
            ],
            'occurred_at' => now(),
        ]);

        // TODO(channels-inbound-pull): dispatch \App\Jobs\PullBookingsFromChannelJob once
        // implemented per connector. The pull.requested log above is the trigger that the
        // worker consumes; success/error rows will be appended by the job (event_type
        // pull.completed / pull.failed) so this UI stays connector-agnostic.

        return redirect()
            ->route('channel-sync.index', $viewer->isSuperAdmin() ? ['tenant' => $tenant->code] : [])
            ->with('system_alert', [
                'icon' => 'success',
                'title' => $travelAgent->name,
                'message' => __('translation.channel-sync-queued-toast', ['agent' => $travelAgent->name]),
            ]);
    }
}
