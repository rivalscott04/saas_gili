<?php

namespace App\Http\Controllers;

use App\Models\TravelAgent;
use App\Services\TravelAgentConnectionService;
use App\Services\TravelAgents\AirbnbOAuthService;
use App\Support\AirbnbPlatformIntegrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AirbnbOAuthController extends Controller
{
    public function __construct(
        private readonly TravelAgentConnectionService $connectionService,
        private readonly AirbnbOAuthService $oauthService,
    ) {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function redirect(Request $request, TravelAgent $travelAgent): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('manageConnection', $travelAgent)) {
            return redirect()->route('root');
        }

        if (! AirbnbPlatformIntegrator::isAirbnbAgent($travelAgent)) {
            return redirect()->route('travel-agents.index');
        }

        if (! AirbnbPlatformIntegrator::isEnabled()) {
            return $this->backWithAlert($viewer, $travelAgent, 'warning', __('translation.airbnb-oauth-not-configured'));
        }

        $tenant = $this->connectionService->resolveTenantForViewer(
            $viewer,
            $viewer->isSuperAdmin() ? trim((string) $request->query('tenant', '')) : null
        );
        if (! $tenant) {
            return redirect()->route('root');
        }

        $url = $this->oauthService->authorizationUrl((int) $tenant->id, (int) $travelAgent->id, (int) $viewer->id);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer) {
            return redirect()->route('root');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('travel-agents.index')
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Airbnb',
                    'message' => (string) $request->query('error_description', $request->query('error')),
                ]);
        }

        $code = trim((string) $request->query('code', ''));
        $state = trim((string) $request->query('state', ''));
        if ($code === '' || $state === '') {
            return redirect()
                ->route('travel-agents.index')
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Airbnb',
                    'message' => __('translation.airbnb-oauth-missing-code'),
                ]);
        }

        $result = $this->oauthService->handleCallback($code, $state);
        $tenantCode = $result['tenant']?->code;

        return redirect()
            ->route('travel-agents.index', $tenantCode ? ['tenant' => $tenantCode] : [])
            ->with('system_alert', [
                'icon' => ($result['ok'] ?? false) ? 'success' : 'warning',
                'title' => 'Airbnb',
                'message' => (string) ($result['message'] ?? ''),
            ]);
    }

    private function backWithAlert($viewer, TravelAgent $travelAgent, string $icon, string $message): RedirectResponse
    {
        $tenant = $this->connectionService->resolveTenantForViewer(
            $viewer,
            $viewer->isSuperAdmin() ? trim((string) request()->query('tenant', '')) : null
        );

        return redirect()
            ->route('travel-agents.index', $tenant ? ['tenant' => $tenant->code] : [])
            ->with('system_alert', [
                'icon' => $icon,
                'title' => $travelAgent->name,
                'message' => $message,
            ]);
    }
}
