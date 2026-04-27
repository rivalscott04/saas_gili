<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertTravelAgentConnectionRequest;
use App\Jobs\PushBookingToGetYourGuideJob;
use App\Models\Booking;
use App\Models\ChannelSyncLog;
use App\Models\Tenant;
use App\Models\TravelAgent;
use App\Models\User;
use App\Services\TravelAgentConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TravelAgentController extends Controller
{
    public function __construct(private readonly TravelAgentConnectionService $service)
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

        $this->service->ensureDefaultTravelAgents();
        $tenant = $this->service->resolveTenantForViewer(
            $viewer,
            $viewer->isSuperAdmin() ? trim((string) $request->query('tenant', '')) : null
        );
        if (! $tenant) {
            return redirect()->route('root');
        }

        $tenantId = (int) $tenant->id;
        $since = Carbon::now()->subDays(7);

        $logCount = static function (int $tenantId, Carbon $since, string $direction, string $status): int {
            return ChannelSyncLog::query()
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $since)
                ->where('direction', $direction)
                ->where('status', $status)
                ->count();
        };

        $gygMetrics = [
            'window_days' => 7,
            'inbound_success' => $logCount($tenantId, $since, 'inbound', 'success'),
            'inbound_error' => $logCount($tenantId, $since, 'inbound', 'error'),
            'outbound_success' => $logCount($tenantId, $since, 'outbound', 'success'),
            'outbound_error' => $logCount($tenantId, $since, 'outbound', 'error'),
        ];

        $failedBookingRetryCount = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('sync_status', 'error')
            ->whereNotNull('external_activity_id')
            ->where('external_activity_id', '!=', '')
            ->count();

        return view('apps-travel-agents', [
            'tenant' => $tenant,
            'travelAgents' => $this->service->listWithTenantConnections($tenantId),
            'brandingMap' => $this->service->brandingMap(),
            'availableTenants' => $this->service->availableTenantsForViewer($viewer),
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
            'gygMetrics' => $gygMetrics,
            'failedBookingRetryCount' => $failedBookingRetryCount,
            'canViewSyncLogs' => $viewer->can('viewAny', \App\Models\ChannelSyncLog::class),
            'canRetryFailedJobs' => $viewer->hasPlatformPermission('platform.travel_agents.retry_failed_jobs'),
        ]);
    }

    public function retryFailedOutbound(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('viewAny', TravelAgent::class) || ! $viewer->hasPlatformPermission('platform.travel_agents.retry_failed_jobs')) {
            return redirect()->route('root');
        }

        $this->service->ensureDefaultTravelAgents();
        $tenant = $this->service->resolveTenantForViewer(
            $viewer,
            $viewer->isSuperAdmin() ? trim((string) $request->input('tenant_code', '')) : null
        );
        if (! $tenant) {
            return redirect()->route('root');
        }

        $queued = 0;
        Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('sync_status', 'error')
            ->whereNotNull('external_activity_id')
            ->where('external_activity_id', '!=', '')
            ->orderByDesc('id')
            ->limit(50)
            ->pluck('id')
            ->each(function (int|string $id) use ($tenant, &$queued): void {
                if (config('services.getyourguide.sync_via_queue', true)) {
                    PushBookingToGetYourGuideJob::dispatch((int) $id, (int) $tenant->id);
                } else {
                    $booking = Booking::query()->find((int) $id);
                    if ($booking) {
                        app(\App\Services\TravelAgents\GetYourGuideBookingSyncService::class)
                            ->syncCreateBooking($booking, (int) $tenant->id);
                    }
                }
                $queued++;
            });

        return redirect()
            ->route('travel-agents.index', ['tenant' => $tenant->code])
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'GetYourGuide',
                'message' => __('translation.gyg-retry-queued', ['count' => $queued]),
            ]);
    }

    public function connect(UpsertTravelAgentConnectionRequest $request, TravelAgent $travelAgent): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('manageConnection', $travelAgent)) {
            return redirect()->route('root');
        }

        $tenant = $this->resolveTenantFromConnectionRequest($viewer, $request);
        if (! $tenant) {
            return redirect()->route('root');
        }

        $payload = $request->validated();
        $this->service->upsertConnection((int) $tenant->id, $travelAgent, $payload);

        return redirect()
            ->route('travel-agents.index', ['tenant' => $tenant->code])
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Koneksi agent tersimpan',
                'message' => 'API credential berhasil disimpan untuk tenant ini.',
            ]);
    }

    public function testConnection(UpsertTravelAgentConnectionRequest $request, TravelAgent $travelAgent): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('testConnection', $travelAgent)) {
            return redirect()->route('root');
        }

        $tenant = $this->resolveTenantFromConnectionRequest($viewer, $request);
        if (! $tenant) {
            return redirect()->route('root');
        }

        $payload = $request->validated();
        $result = $this->service->testConnection((int) $tenant->id, $travelAgent, $payload);

        return redirect()
            ->route('travel-agents.index', ['tenant' => $tenant->code])
            ->with('system_alert', [
                'icon' => $result['ok'] ? 'success' : 'warning',
                'title' => $result['ok'] ? 'Test koneksi berhasil' : 'Test koneksi gagal',
                'message' => $result['message'],
            ]);
    }

    public function disconnect(Request $request, TravelAgent $travelAgent): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('manageConnection', $travelAgent)) {
            return redirect()->route('root');
        }

        $tenant = $this->resolveTenantFromConnectionRequest($viewer, $request);
        if (! $tenant) {
            return redirect()->route('root');
        }

        $this->service->disconnect((int) $tenant->id, $travelAgent);

        return redirect()
            ->route('travel-agents.index', ['tenant' => $tenant->code])
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Koneksi diputus',
                'message' => 'Credential agent telah direset ke status disconnected.',
            ]);
    }

    private function resolveTenantFromConnectionRequest(User $viewer, Request $request): ?Tenant
    {
        if ($viewer->isSuperAdmin()) {
            $code = trim((string) $request->input('tenant_code', ''));

            return $this->service->resolveTenantForViewer($viewer, $code);
        }

        return $this->service->resolveTenantForViewer($viewer, null);
    }
}
