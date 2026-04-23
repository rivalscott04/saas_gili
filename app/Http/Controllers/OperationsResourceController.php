<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantResource;
use App\Models\Tour;
use App\Models\TourResourceRequirement;
use App\Support\TenantWebScope;
use App\Support\ValidationMessages\OperationsResourceValidationMessages;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperationsResourceController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const RESOURCE_TYPES = [
        'vehicle' => 'Fleet / Vehicles',
        'guide_driver' => 'Guides & Drivers',
        'equipment' => 'Equipment',
    ];

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

        $selectedTenantId = $this->resolveTenantScope($request, $viewer, $availableTenants);
        if ($selectedTenantId <= 0) {
            return redirect()->route('root');
        }

        $tenant = Tenant::query()->find($selectedTenantId);
        $availableTours = Tour::query()
            ->where('tenant_id', $selectedTenantId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'resource_type' => trim((string) $request->query('resource_type', '')),
            'status' => trim((string) $request->query('status', '')),
            'required_by_active_tour' => trim((string) $request->query('required_by_active_tour', '')),
        ];

        $activeRequiredTypes = TourResourceRequirement::query()
            ->where('tenant_id', $selectedTenantId)
            ->where('is_required', true)
            ->whereHas('tour', fn ($q) => $q->where('is_active', true))
            ->select('resource_type')
            ->distinct()
            ->pluck('resource_type')
            ->all();

        $resourceQuery = TenantResource::query()
            ->where('tenant_id', $selectedTenantId)
            ->orderBy('resource_type')
            ->orderBy('name');
        if ($filters['q'] !== '') {
            $resourceQuery->where(function ($query) use ($filters): void {
                $query->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('reference_code', 'like', '%'.$filters['q'].'%')
                    ->orWhere('notes', 'like', '%'.$filters['q'].'%');
            });
        }
        if (array_key_exists($filters['resource_type'], self::RESOURCE_TYPES)) {
            $resourceQuery->where('resource_type', $filters['resource_type']);
        }
        if (in_array($filters['status'], ['available', 'blocked'], true)) {
            $resourceQuery->where('status', $filters['status']);
        }
        if ($filters['required_by_active_tour'] === '1') {
            if ($activeRequiredTypes === []) {
                $resourceQuery->whereRaw('1 = 0');
            } else {
                $resourceQuery->whereIn('resource_type', $activeRequiredTypes);
            }
        }
        /** @var LengthAwarePaginator $resources */
        $resources = $resourceQuery
            ->paginate(12)
            ->withQueryString();

        $tourRequirementsByResourceType = TourResourceRequirement::query()
            ->where('tenant_id', $selectedTenantId)
            ->where('is_required', true)
            ->with('tour:id,name,is_active')
            ->get()
            ->groupBy('resource_type')
            ->map(function ($rows): array {
                return $rows
                    ->map(function (TourResourceRequirement $row): ?array {
                        if (! $row->tour) {
                            return null;
                        }

                        return [
                            'tour_id' => (int) $row->tour_id,
                            'tour_name' => (string) $row->tour->name,
                            'is_active' => (bool) $row->tour->is_active,
                            'min_units' => max(1, (int) $row->min_units),
                        ];
                    })
                    ->filter()
                    ->unique('tour_id')
                    ->values()
                    ->all();
            })
            ->toArray();

        $viewData = [
            'tenant' => $tenant,
            'resources' => $resources,
            'resourceTypes' => self::RESOURCE_TYPES,
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
            'availableTenants' => $availableTenants,
            'availableTours' => $availableTours,
            'filters' => $filters,
            'tourRequirementsByResourceType' => $tourRequirementsByResourceType,
            'activeRequiredTypes' => $activeRequiredTypes,
        ];

        if ($request->ajax()) {
            return response()->view('partials.operations-resource-list', $viewData);
        }

        return view('apps-operations-resources', $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $rules = [
            'resource_type' => ['required', Rule::in(array_keys(self::RESOURCE_TYPES))],
            'name' => ['required', 'string', 'max:160'],
            'reference_code' => ['required', 'string', 'max:120'],
            'capacity' => [
                Rule::requiredIf(
                    in_array((string) $request->input('resource_type'), ['vehicle', 'equipment'], true)
                ),
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sync_tour_usage' => ['nullable', Rule::in(['0', '1'])],
            'tour_ids' => ['nullable', 'array'],
            'tour_ids.*' => ['nullable', 'integer', Rule::exists('tours', 'id')],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
        }
        $payload = $request->validate($rules, OperationsResourceValidationMessages::store());

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $payload);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }

        TenantResource::query()->create([
            'tenant_id' => $tenantId,
            'resource_type' => $payload['resource_type'],
            'name' => $payload['name'],
            'reference_code' => $payload['reference_code'] ?? null,
            'capacity' => $payload['capacity'] ?? null,
            'status' => 'available',
            'notes' => $payload['notes'] ?? null,
        ]);

        $alert = [
            'icon' => 'success',
            'title' => 'Resource tersimpan',
            'message' => 'Resource baru berhasil ditambahkan untuk tenant ini.',
        ];
        if ($request->ajax()) {
            return $this->ajaxSuccessResponse($request, $tenantId, $viewer, $alert);
        }

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', $alert);
    }

    public function update(Request $request, TenantResource $resource): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $rules = [
            'resource_type' => ['required', Rule::in(array_keys(self::RESOURCE_TYPES))],
            'name' => ['required', 'string', 'max:160'],
            'reference_code' => ['required', 'string', 'max:120'],
            'capacity' => [
                Rule::requiredIf(
                    in_array((string) $request->input('resource_type'), ['vehicle', 'equipment'], true)
                ),
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sync_tour_usage' => ['nullable', Rule::in(['0', '1'])],
            'tour_ids' => ['nullable', 'array'],
            'tour_ids.*' => ['nullable', 'integer', Rule::exists('tours', 'id')],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
        }
        $payload = $request->validate($rules, OperationsResourceValidationMessages::store());

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $payload);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        if ((int) $resource->tenant_id !== $tenantId) {
            abort(403);
        }

        $resource->update([
            'resource_type' => $payload['resource_type'],
            'name' => $payload['name'],
            'reference_code' => $payload['reference_code'] ?? null,
            'capacity' => $payload['capacity'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);
        if ((string) ($payload['sync_tour_usage'] ?? '0') === '1') {
            $selectedTourIds = $this->normalizeTourIdList($payload['tour_ids'] ?? []);
            $this->syncResourceTourUsage($tenantId, $payload['resource_type'], $selectedTourIds);
        }

        $alert = [
            'icon' => 'success',
            'title' => 'Resource diperbarui',
            'message' => 'Data resource berhasil diperbarui.',
        ];
        if ($request->ajax()) {
            return $this->ajaxSuccessResponse($request, $tenantId, $viewer, $alert);
        }

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', $alert);
    }

    public function blockOut(Request $request, TenantResource $resource): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $rules = [
            'status' => ['required', Rule::in(['available', 'blocked'])],
            'blocked_from' => [
                Rule::requiredIf((string) $request->input('status') === 'blocked'),
                'nullable',
                'date',
            ],
            'blocked_until' => ['nullable', 'date', 'after_or_equal:blocked_from'],
            'block_reason' => [
                Rule::requiredIf((string) $request->input('status') === 'blocked'),
                'nullable',
                'string',
                'max:255',
            ],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
        }
        $payload = $request->validate($rules, OperationsResourceValidationMessages::blockOut());

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $payload);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        if ((int) $resource->tenant_id !== $tenantId) {
            abort(403);
        }

        $resource->status = $payload['status'];
        if ($payload['status'] === 'blocked') {
            $resource->blocked_from = $payload['blocked_from'] ?? now();
            $resource->blocked_until = $payload['blocked_until'] ?? null;
            $resource->block_reason = $payload['block_reason'] ?? null;
        } else {
            $resource->blocked_from = null;
            $resource->blocked_until = null;
            $resource->block_reason = null;
        }
        $resource->save();

        $alert = [
            'icon' => 'success',
            'title' => 'Availability diperbarui',
            'message' => 'Status resource sudah diperbarui.',
        ];
        if ($request->ajax()) {
            return $this->ajaxSuccessResponse($request, $tenantId, $viewer, $alert);
        }

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', $alert);
    }

    public function destroy(Request $request, TenantResource $resource): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        if ((int) $resource->tenant_id !== $tenantId) {
            abort(403);
        }

        $resource->delete();

        $alert = [
            'icon' => 'success',
            'title' => 'Resource dihapus',
            'message' => 'Data resource sudah dihapus dari tenant ini.',
        ];
        if ($request->ajax()) {
            return $this->ajaxSuccessResponse($request, $tenantId, $viewer, $alert);
        }

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', $alert);
    }

    private function resolveTenantScope(Request $request, User $viewer, $availableTenants): int
    {
        return TenantWebScope::resolveTenantId($request, $viewer, $availableTenants);
    }

    private function redirectToIndex(int $tenantId, User $viewer): RedirectResponse
    {
        if ($viewer->isSuperAdmin()) {
            $tenantCode = (string) Tenant::query()->whereKey($tenantId)->value('code');
            if ($tenantCode !== '') {
                return redirect()->route('operations-resources.index', ['tenant' => $tenantCode]);
            }
        }

        return redirect()->route('operations-resources.index');
    }

    /**
     * @param array<string, string> $alert
     */
    private function ajaxSuccessResponse(Request $request, int $tenantId, User $viewer, array $alert): JsonResponse
    {
        $fallbackUrl = $this->redirectToIndex($tenantId, $viewer)->getTargetUrl();
        $currentUrl = trim((string) $request->header('X-Current-Url', ''));
        $redirectUrl = $currentUrl !== '' ? $currentUrl : $fallbackUrl;

        return response()->json([
            'redirect_url' => $redirectUrl,
            'system_alert' => $alert,
        ]);
    }

    /**
     * @param  mixed  $tourIds
     * @return array<int, int>
     */
    private function normalizeTourIdList(mixed $tourIds): array
    {
        return collect((array) $tourIds)
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $selectedTourIds
     */
    private function syncResourceTourUsage(int $tenantId, string $resourceType, array $selectedTourIds): void
    {
        $tenantTourIds = Tour::query()
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        if ($tenantTourIds === []) {
            return;
        }

        $tenantTourLookup = array_fill_keys($tenantTourIds, true);
        foreach ($selectedTourIds as $tourId) {
            if (! isset($tenantTourLookup[$tourId])) {
                throw ValidationException::withMessages([
                    'tour_ids' => 'Pilihan tour tidak valid untuk tenant ini.',
                ]);
            }
        }

        $selectedLookup = array_fill_keys($selectedTourIds, true);
        $existingRows = TourResourceRequirement::query()
            ->where('tenant_id', $tenantId)
            ->where('resource_type', $resourceType)
            ->whereIn('tour_id', $tenantTourIds)
            ->get()
            ->keyBy(fn (TourResourceRequirement $row): int => (int) $row->tour_id);

        $now = now();
        $insertRows = [];
        foreach ($tenantTourIds as $tourId) {
            $isRequired = isset($selectedLookup[$tourId]);
            /** @var TourResourceRequirement|null $existing */
            $existing = $existingRows->get($tourId);

            if ($existing) {
                $expectedMinUnits = max(1, (int) $existing->min_units);
                if ((bool) $existing->is_required !== $isRequired || (int) $existing->min_units !== $expectedMinUnits) {
                    $existing->is_required = $isRequired;
                    $existing->min_units = $expectedMinUnits;
                    $existing->save();
                }
                continue;
            }

            if (! $isRequired) {
                continue;
            }

            $insertRows[] = [
                'tenant_id' => $tenantId,
                'tour_id' => $tourId,
                'resource_type' => $resourceType,
                'is_required' => true,
                'min_units' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($insertRows !== []) {
            TourResourceRequirement::query()->insert($insertRows);
        }
    }
}
