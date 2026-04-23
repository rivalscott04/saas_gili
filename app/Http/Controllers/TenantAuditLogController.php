<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\Tour;
use App\Support\TenantWebScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAuditLogController extends Controller
{
    public function __construct()
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

        $availableTenants = collect();
        if ($viewer->isSuperAdmin()) {
            $availableTenants = Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        $tenantId = TenantWebScope::resolveTenantId($request, $viewer, $availableTenants);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }

        $filters = [
            'tour_id' => trim((string) $request->query('tour_id', '')),
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
            'event_type' => trim((string) $request->query('event_type', '')),
        ];

        $logs = TenantAuditLog::query()
            ->where('tenant_id', $tenantId)
            ->when($filters['tour_id'] !== '', fn ($q) => $q->where('tour_id', (int) $filters['tour_id']))
            ->when($filters['event_type'] !== '', fn ($q) => $q->where('event_type', $filters['event_type']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('occurred_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('occurred_at', '<=', $filters['to']))
            ->with(['actor:id,name,email', 'tour:id,name'])
            ->orderByDesc('occurred_at')
            ->paginate(30)
            ->withQueryString();

        $tenant = Tenant::query()->find($tenantId);
        $tours = Tour::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $eventTypes = TenantAuditLog::query()
            ->where('tenant_id', $tenantId)
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->values();

        return view('apps-audit-logs', [
            'tenant' => $tenant,
            'availableTenants' => $availableTenants,
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
            'filters' => $filters,
            'logs' => $logs,
            'tours' => $tours,
            'eventTypes' => $eventTypes,
        ]);
    }
}
