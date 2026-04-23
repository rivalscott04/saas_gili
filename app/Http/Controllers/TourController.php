<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TourResourceRequirement;
use App\Models\User;
use App\Support\TenantWebScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TourController extends Controller
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
            ->with('resourceRequirements')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('apps-tours', [
            'tenant' => $tenant,
            'tours' => $tours,
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

        $payload = $request->validate([
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('tours', 'name')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'default_max_pax_per_day' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'in:0,1'],
            'allocation_requirement' => [
                'nullable',
                'string',
                Rule::in([Tour::ALLOCATION_NONE, Tour::ALLOCATION_SNORKELING, Tour::ALLOCATION_LAND_ACTIVITY]),
            ],
            'requirements' => ['nullable', 'array'],
            'requirements.vehicle.is_required' => ['nullable', 'in:0,1'],
            'requirements.vehicle.min_units' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'requirements.guide_driver.is_required' => ['nullable', 'in:0,1'],
            'requirements.guide_driver.min_units' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'requirements.equipment.is_required' => ['nullable', 'in:0,1'],
            'requirements.equipment.min_units' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'tenant_code' => $viewer->isSuperAdmin() ? ['required', 'string'] : ['prohibited'],
            'code' => ['prohibited'],
        ]);

        $allocationRequirement = $this->normalizeAllocationRequirement($payload['allocation_requirement'] ?? null);
        $tour = Tour::query()->create([
            'tenant_id' => $tenantId,
            'name' => trim((string) $payload['name']),
            'description' => $this->normalizeOptionalString($payload['description'] ?? null),
            'default_max_pax_per_day' => array_key_exists('default_max_pax_per_day', $payload) && $payload['default_max_pax_per_day'] !== null
                ? (int) $payload['default_max_pax_per_day']
                : null,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => ($payload['is_active'] ?? '1') === '1',
            'allocation_requirement' => $allocationRequirement,
        ]);
        $this->syncResourceRequirements($tour, $payload['requirements'] ?? [], $allocationRequirement);

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', [
            'icon' => 'success',
            'title' => 'Tour tersimpan',
            'message' => 'Master tour baru berhasil ditambahkan.',
        ]);
    }

    public function update(Request $request, Tour $tour): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        if ((int) $tour->tenant_id !== $tenantId) {
            abort(403);
        }

        $payload = $request->validate([
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('tours', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($tour->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'default_max_pax_per_day' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'in:0,1'],
            'allocation_requirement' => [
                'nullable',
                'string',
                Rule::in([Tour::ALLOCATION_NONE, Tour::ALLOCATION_SNORKELING, Tour::ALLOCATION_LAND_ACTIVITY]),
            ],
            'requirements' => ['nullable', 'array'],
            'requirements.vehicle.is_required' => ['nullable', 'in:0,1'],
            'requirements.vehicle.min_units' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'requirements.guide_driver.is_required' => ['nullable', 'in:0,1'],
            'requirements.guide_driver.min_units' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'requirements.equipment.is_required' => ['nullable', 'in:0,1'],
            'requirements.equipment.min_units' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'tenant_code' => $viewer->isSuperAdmin()
                ? ['required', 'string', Rule::exists('tenants', 'code')]
                : ['prohibited'],
            'code' => ['prohibited'],
        ]);

        $allocationRequirement = $this->normalizeAllocationRequirement($payload['allocation_requirement'] ?? null);
        $tour->update([
            'name' => trim((string) $payload['name']),
            'description' => $this->normalizeOptionalString($payload['description'] ?? null),
            'default_max_pax_per_day' => array_key_exists('default_max_pax_per_day', $payload) && $payload['default_max_pax_per_day'] !== null
                ? (int) $payload['default_max_pax_per_day']
                : null,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => ($payload['is_active'] ?? '1') === '1',
            'allocation_requirement' => $allocationRequirement,
        ]);
        $this->syncResourceRequirements($tour, $payload['requirements'] ?? [], $allocationRequirement);

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', [
            'icon' => 'success',
            'title' => 'Tour diperbarui',
            'message' => 'Perubahan data tour sudah tersimpan.',
        ]);
    }

    public function archive(Request $request, Tour $tour): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        if ((int) $tour->tenant_id !== $tenantId) {
            abort(403);
        }

        $tour->update(['is_active' => false]);

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', [
            'icon' => 'success',
            'title' => 'Tour diarsipkan',
            'message' => 'Tour diarsipkan dan tidak tampil di pilihan booking manual.',
        ]);
    }

    private function redirectToIndex(int $tenantId, User $viewer): RedirectResponse
    {
        if ($viewer->isSuperAdmin()) {
            $tenantCode = (string) Tenant::query()->whereKey($tenantId)->value('code');
            if ($tenantCode !== '') {
                return redirect()->route('tours.index', ['tenant' => $tenantCode]);
            }
        }

        return redirect()->route('tours.index');
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeAllocationRequirement(mixed $value): string
    {
        $raw = strtolower(trim((string) $value));
        $allowed = [Tour::ALLOCATION_NONE, Tour::ALLOCATION_SNORKELING, Tour::ALLOCATION_LAND_ACTIVITY];
        if ($raw === '' || ! in_array($raw, $allowed, true)) {
            return Tour::ALLOCATION_NONE;
        }

        return $raw;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array{is_required: bool, min_units: int}>
     */
    private function normalizeRequirementPayload(array $payload, string $allocationRequirement): array
    {
        $defaults = [
            'vehicle' => ['is_required' => false, 'min_units' => 1],
            'guide_driver' => ['is_required' => false, 'min_units' => 1],
            'equipment' => ['is_required' => false, 'min_units' => 1],
        ];
        if ($allocationRequirement === Tour::ALLOCATION_SNORKELING) {
            $defaults['vehicle']['is_required'] = true;
        } elseif ($allocationRequirement === Tour::ALLOCATION_LAND_ACTIVITY) {
            $defaults['vehicle']['is_required'] = true;
            $defaults['guide_driver']['is_required'] = true;
        }

        foreach (array_keys($defaults) as $resourceType) {
            $row = (array) ($payload[$resourceType] ?? []);
            if (array_key_exists('is_required', $row)) {
                $defaults[$resourceType]['is_required'] = (string) $row['is_required'] === '1';
            }
            if (array_key_exists('min_units', $row) && $row['min_units'] !== null && $row['min_units'] !== '') {
                $defaults[$resourceType]['min_units'] = max(1, (int) $row['min_units']);
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $requirementsPayload
     */
    private function syncResourceRequirements(Tour $tour, array $requirementsPayload, string $allocationRequirement): void
    {
        $normalized = $this->normalizeRequirementPayload($requirementsPayload, $allocationRequirement);
        TourResourceRequirement::query()->where('tour_id', $tour->id)->delete();

        $now = now();
        $rows = [];
        foreach ($normalized as $resourceType => $row) {
            $rows[] = [
                'tenant_id' => $tour->tenant_id,
                'tour_id' => $tour->id,
                'resource_type' => $resourceType,
                'is_required' => $row['is_required'],
                'min_units' => $row['min_units'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows !== []) {
            TourResourceRequirement::query()->insert($rows);
        }
    }
}
