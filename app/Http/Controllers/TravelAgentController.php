<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertTravelAgentConnectionRequest;
use App\Models\Tenant;
use App\Models\TravelAgent;
use App\Models\User;
use App\Services\TravelAgentConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        if (! $viewer || ! $viewer->isAdmin()) {
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

        return view('apps-travel-agents', [
            'tenant' => $tenant,
            'travelAgents' => $this->service->listWithTenantConnections((int) $tenant->id),
            'brandingMap' => $this->service->brandingMap(),
            'availableTenants' => $this->service->availableTenantsForViewer($viewer),
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
        ]);
    }

    public function connect(UpsertTravelAgentConnectionRequest $request, TravelAgent $travelAgent): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
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
        if (! $viewer || ! $viewer->isAdmin()) {
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
        if (! $viewer || ! $viewer->isAdmin()) {
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
