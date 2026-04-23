<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use App\Models\User;
use App\Support\TenantWebScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TourDayCapacityController extends Controller
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

        $selectedTenantId = TenantWebScope::resolveTenantId($request, $viewer, $availableTenants);
        if ($selectedTenantId <= 0) {
            return redirect()->route('root');
        }

        $tenant = Tenant::query()->find($selectedTenantId);
        if (! $tenant) {
            return redirect()->route('root');
        }

        $tours = Tour::query()
            ->where('tenant_id', $selectedTenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tourId = (int) $request->query('tour_id', 0);
        $selectedTour = $tourId > 0
            ? Tour::query()->where('tenant_id', $selectedTenantId)->whereKey($tourId)->first()
            : null;

        $capacities = null;
        if ($selectedTour) {
            $capacities = TourDayCapacity::query()
                ->where('tour_id', $selectedTour->id)
                ->orderBy('service_date')
                ->paginate(20)
                ->withQueryString();
        }

        return view('apps-tour-day-capacities', [
            'tenant' => $tenant,
            'tours' => $tours,
            'selectedTour' => $selectedTour,
            'capacities' => $capacities,
            'availableTenants' => $availableTenants,
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        if ($viewer->isSuperAdmin()) {
            $request->validate([
                'tenant_code' => ['required', 'string', Rule::exists('tenants', 'code')],
            ]);
        }

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }

        $rules = [
            'tour_id' => ['required', 'integer', Rule::exists('tours', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'service_date' => ['required', 'date'],
            'max_pax' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string'];
        } else {
            $rules['tenant_code'] = ['prohibited'];
        }
        $payload = $request->validate($rules);

        $serviceDate = Carbon::parse((string) $payload['service_date'])->startOfDay();

        TourDayCapacity::query()->updateOrCreate(
            [
                'tour_id' => (int) $payload['tour_id'],
                'service_date' => $serviceDate,
            ],
            [
                'tenant_id' => $tenantId,
                'max_pax' => (int) $payload['max_pax'],
            ]
        );

        return $this->redirectToIndex($tenantId, $viewer, (int) $payload['tour_id'])->with('system_alert', [
            'icon' => 'success',
            'title' => 'Kapasitas harian disimpan',
            'message' => 'Override max pax untuk tanggal tersebut sudah diperbarui.',
        ]);
    }

    public function destroy(Request $request, TourDayCapacity $capacity): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        if ($viewer->isSuperAdmin()) {
            $request->validate([
                'tenant_code' => ['required', 'string', Rule::exists('tenants', 'code')],
            ]);
        } else {
            $request->validate([
                'tenant_code' => ['prohibited'],
            ]);
        }

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($tenantId <= 0 || (int) $capacity->tenant_id !== $tenantId) {
            abort(403);
        }

        $tourId = (int) $capacity->tour_id;
        $capacity->delete();

        return $this->redirectToIndex($tenantId, $viewer, $tourId)->with('system_alert', [
            'icon' => 'success',
            'title' => 'Override dihapus',
            'message' => 'Kapasitas harian kembali memakai default tour.',
        ]);
    }

    private function redirectToIndex(int $tenantId, User $viewer, int $tourId): RedirectResponse
    {
        $query = $tourId > 0 ? ['tour_id' => $tourId] : [];
        if ($viewer->isSuperAdmin()) {
            $tenantCode = (string) Tenant::query()->whereKey($tenantId)->value('code');
            if ($tenantCode !== '') {
                return redirect()->route('tour-day-capacities.index', array_merge(['tenant' => $tenantCode], $query));
            }
        }

        return redirect()->route('tour-day-capacities.index', $query);
    }
}
