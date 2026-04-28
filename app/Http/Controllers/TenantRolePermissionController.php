<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Support\TenantPermissionCatalog;
use App\Support\TenantWebScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantRolePermissionController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const SYSTEM_ROLES = [
        'tenant_admin' => 'Tenant Admin',
        'operator' => 'Operator',
        'guide' => 'Guide',
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
            $availableTenants = Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        }

        $selectedTenantId = TenantWebScope::resolveTenantId($request, $viewer, $availableTenants);

        if ($selectedTenantId <= 0) {
            return redirect()->route('root');
        }
        $this->ensureSystemRoles($selectedTenantId);

        $roles = TenantRole::query()
            ->where('tenant_id', $selectedTenantId)
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_system']);
        $selectedRole = (string) $request->query('role', 'operator');
        if (! $roles->contains(fn (TenantRole $role): bool => $role->code === $selectedRole)) {
            $selectedRole = (string) optional($roles->first())->code;
        }

        $stored = DB::table('tenant_role_permissions')
            ->where('tenant_id', $selectedTenantId)
            ->where('role', $selectedRole)
            ->pluck('is_allowed', 'permission_key')
            ->toArray();

        $permissionMap = [];
        foreach (TenantPermissionCatalog::LABELS as $key => $label) {
            $isAllowedByDefault = in_array($key, TenantPermissionCatalog::defaultsForRole($selectedRole), true);
            $permissionMap[] = [
                'key' => $key,
                'label' => $label,
                'is_allowed' => array_key_exists($key, $stored) ? (bool) $stored[$key] : $isAllowedByDefault,
            ];
        }

        return view('apps-tenant-role-permissions', [
            'tenant' => Tenant::query()->find($selectedTenantId),
            'availableTenants' => $availableTenants,
            'selectedTenantId' => $selectedTenantId,
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'permissions' => $permissionMap,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        if ($viewer->isSuperAdmin()) {
            $request->validate([
                'tenant_code' => ['required', 'string', 'max:120', Rule::exists('tenants', 'code')],
            ]);
        } else {
            if ($request->has('tenant_code') || $request->has('tenant_id')) {
                abort(403);
            }
        }

        $payloadTenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($payloadTenantId <= 0) {
            return redirect()->route('root');
        }
        $this->ensureSystemRoles($payloadTenantId);

        $roleCodes = TenantRole::query()
            ->where('tenant_id', $payloadTenantId)
            ->pluck('code')
            ->values()
            ->all();

        $payload = $request->validate([
            'role' => ['required', 'string', Rule::in($roleCodes)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(TenantPermissionCatalog::LABELS))],
        ]);

        $enabled = collect($payload['permissions'] ?? [])->values()->all();
        $now = now();

        $permissionRows = collect(array_keys(TenantPermissionCatalog::LABELS))
            ->map(fn (string $permissionKey): array => [
                'tenant_id' => $payloadTenantId,
                'role' => $payload['role'],
                'permission_key' => $permissionKey,
                'is_allowed' => in_array($permissionKey, $enabled, true),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
        DB::table('tenant_role_permissions')->upsert(
            $permissionRows,
            ['tenant_id', 'role', 'permission_key'],
            ['is_allowed', 'updated_at']
        );

        $tenantCode = (string) Tenant::query()->whereKey($payloadTenantId)->value('code');

        return redirect()
            ->route('tenant-role-permissions.index', [
                'role' => $payload['role'],
                'tenant' => $tenantCode,
            ])
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Permission role diperbarui',
                'message' => 'Pengaturan akses role untuk tenant ini sudah tersimpan.',
            ]);
    }

    private function ensureSystemRoles(int $tenantId): void
    {
        foreach (self::SYSTEM_ROLES as $code => $name) {
            TenantRole::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'is_system' => true,
                ]
            );
        }
    }
}
