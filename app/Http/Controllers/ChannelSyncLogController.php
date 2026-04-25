<?php

namespace App\Http\Controllers;

use App\Models\ChannelSyncLog;
use App\Models\Tenant;
use App\Models\TravelAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChannelSyncLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->can('viewAny', ChannelSyncLog::class)) {
            return redirect()->route('root');
        }

        $query = ChannelSyncLog::query()->with('travelAgent')->orderByDesc('id');
        if (! $viewer->isSuperAdmin()) {
            $query->where('tenant_id', $viewer->tenant_id);
        } else {
            $tenantCode = trim((string) $request->query('tenant', ''));
            if ($tenantCode !== '') {
                $tenantId = (int) Tenant::query()->whereRaw('LOWER(code) = ?', [strtolower($tenantCode)])->value('id');
                if ($tenantId > 0) {
                    $query->where('tenant_id', $tenantId);
                }
            }
        }

        if ($request->filled('agent')) {
            $agentCode = strtolower(trim((string) $request->query('agent')));
            $agentId = (int) TravelAgent::query()->whereRaw('LOWER(code) = ?', [$agentCode])->value('id');
            if ($agentId > 0) {
                $query->where('travel_agent_id', $agentId);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', 'like', '%'.(string) $request->query('event_type').'%');
        }

        return view('apps-channel-sync-logs', [
            'logs' => $query->paginate(20)->withQueryString(),
            'travelAgents' => TravelAgent::query()->orderBy('name')->get(['id', 'name', 'code']),
            'availableTenants' => $viewer->isSuperAdmin()
                ? Tenant::query()->orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
            'filters' => [
                'tenant' => (string) $request->query('tenant', ''),
                'agent' => (string) $request->query('agent', ''),
                'status' => (string) $request->query('status', ''),
                'event_type' => (string) $request->query('event_type', ''),
            ],
        ]);
    }
}
