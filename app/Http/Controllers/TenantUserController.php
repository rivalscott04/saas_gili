<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\User;
use App\Support\TenantPermissionCatalog;
use App\Support\TenantWebScope;
use App\Support\ValidationMessages\TenantUserValidationMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantUserController extends Controller
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

        $selectedTenantId = $this->resolveTenantScope($request, $viewer, $availableTenants);
        if ($selectedTenantId <= 0) {
            return redirect()->route('root');
        }

        if ($viewer->isSuperAdmin()) {
            $selectedTenant = $availableTenants->first(fn ($tenant) => (int) $tenant->id === $selectedTenantId);
            $selectedTenantCode = (string) ($selectedTenant->code ?? '');
            $requestedTenantCode = trim((string) $request->query('tenant', ''));
            if ($selectedTenantCode !== '' && $requestedTenantCode !== $selectedTenantCode) {
                return redirect()->route('tenant-users.index', ['tenant' => $selectedTenantCode]);
            }
        }
        $this->ensureSystemRoles($selectedTenantId);

        $users = User::query()
            ->where('tenant_id', $selectedTenantId)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $roles = TenantRole::query()
            ->where('tenant_id', $selectedTenantId)
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
        $tenant = Tenant::query()->find($selectedTenantId);
        $maxUsers = (int) ($tenant?->max_users ?? 5);
        $totalUsers = User::query()->where('tenant_id', $selectedTenantId)->count();
        $adminCount = User::query()
            ->where('tenant_id', $selectedTenantId)
            ->where('role', 'tenant_admin')
            ->count();
        $customRoles = $roles->where('is_system', false);
        $maxCustomRoles = max(0, $maxUsers - $adminCount);
        $showTenantSwitcher = $viewer->isSuperAdmin() && $availableTenants->isNotEmpty();

        return view('apps-tenant-users', [
            'users' => $users,
            'roles' => $roles,
            'tenant' => $tenant,
            'availableTenants' => $availableTenants,
            'selectedTenantId' => $selectedTenantId,
            'showTenantSwitcher' => $showTenantSwitcher,
            'maxUsers' => $maxUsers,
            'totalUsers' => $totalUsers,
            'remainingSeats' => max(0, $maxUsers - $totalUsers),
            'customRolesCount' => $customRoles->count(),
            'maxCustomRoles' => $maxCustomRoles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }
        $this->ensureTenantScopeNotOverridden($request, $viewer);

        $rules = [
            'name' => ['required', 'string', 'max:255', 'regex:/.*\S.*/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
            $rules['role'] = ['required', 'string'];
        } else {
            $tenantId = (int) ($viewer->tenant_id ?? 0);
            if ($tenantId <= 0) {
                return redirect()->route('root');
            }
            $this->ensureSystemRoles($tenantId);
            $roleCodes = TenantRole::query()
                ->where('tenant_id', $tenantId)
                ->pluck('code')
                ->values()
                ->all();
            $rules['role'] = ['required', Rule::in($roleCodes)];
        }

        $payload = $request->validate($rules, TenantUserValidationMessages::storeUser());

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $payload);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        $this->ensureSystemRoles($tenantId);

        $roleCodes = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->pluck('code')
            ->values()
            ->all();
        if (! in_array($payload['role'], $roleCodes, true)) {
            return $this->redirectToTenantUsersIndex($tenantId, $viewer)
                ->withInput()
                ->withErrors(['role' => 'Role tidak valid untuk tenant ini.']);
        }
        $tenant = Tenant::query()->find($tenantId);
        $maxUsers = (int) ($tenant?->max_users ?? 5);
        $userCount = User::query()->where('tenant_id', $tenantId)->count();
        if ($userCount >= $maxUsers) {
            return $this->redirectToTenantUsersIndex($tenantId, $viewer)
                ->withInput()
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Kuota user paket habis',
                    'message' => 'Tidak bisa menambah user baru. Upgrade paket untuk menambah seat.',
                ]);
        }

        User::query()->create([
            'tenant_id' => $tenantId,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'password' => Hash::make($payload['password']),
            'status' => 'active',
        ]);

        return $this->redirectToTenantUsersIndex($tenantId, $viewer)
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'User berhasil ditambahkan',
                'message' => 'Akun user/guide baru sudah dibuat.',
            ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }
        $this->ensureTenantScopeNotOverridden($request, $viewer);

        $rules = [
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
        }
        $payload = $request->validate($rules, TenantUserValidationMessages::updateUserStatus());

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $payload);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        $this->ensureSystemRoles($tenantId);

        if ((int) $user->tenant_id !== $tenantId) {
            abort(403);
        }

        if ((int) $user->id === (int) $viewer->id && $payload['status'] === 'suspended') {
            return $this->redirectToTenantUsersIndex($tenantId, $viewer)
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Aksi ditolak',
                    'message' => 'Tidak bisa suspend akun yang sedang dipakai login.',
                ]);
        }

        $user->status = $payload['status'];
        $user->save();

        return $this->redirectToTenantUsersIndex($tenantId, $viewer)
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Status user diperbarui',
                'message' => 'Perubahan status user berhasil disimpan.',
            ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }
        $this->ensureTenantScopeNotOverridden($request, $viewer);

        $rules = [
            'role_name' => ['required', 'string', 'max:120', 'regex:/.*\S.*/'],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
        }
        $payload = $request->validate($rules, TenantUserValidationMessages::storeRole());

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $payload);
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }
        $this->ensureSystemRoles($tenantId);

        $tenant = Tenant::query()->find($tenantId);
        $maxUsers = (int) ($tenant?->max_users ?? 5);
        $adminCount = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'tenant_admin')
            ->count();
        $maxCustomRoles = max(0, $maxUsers - $adminCount);
        $customRolesCount = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->where('is_system', false)
            ->count();

        if ($customRolesCount >= $maxCustomRoles) {
            return $this->redirectToTenantUsersIndex($tenantId, $viewer)
                ->withInput()
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Batas role custom tercapai',
                    'message' => 'Jumlah role custom mengikuti sisa seat paket tenant.',
                ]);
        }

        $baseCode = Str::slug((string) $payload['role_name'], '_');
        $baseCode = $baseCode !== '' ? $baseCode : 'role';
        $code = $baseCode;
        $suffix = 1;
        while (
            TenantRole::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists()
        ) {
            $suffix++;
            $code = $baseCode.'_'.$suffix;
        }

        TenantRole::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $payload['role_name'],
            'is_system' => false,
        ]);
        $now = now();
        $permissionRows = collect(array_keys(TenantPermissionCatalog::LABELS))
            ->map(fn (string $permissionKey): array => [
                'tenant_id' => $tenantId,
                'role' => $code,
                'permission_key' => $permissionKey,
                'is_allowed' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
        DB::table('tenant_role_permissions')->upsert(
            $permissionRows,
            ['tenant_id', 'role', 'permission_key'],
            ['is_allowed', 'updated_at']
        );

        return $this->redirectToTenantUsersIndex($tenantId, $viewer)
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Role custom ditambahkan',
                'message' => 'Sekarang atur permission role ini di menu Role Permissions.',
            ]);
    }

    private function redirectToTenantUsersIndex(int $tenantId, User $viewer): RedirectResponse
    {
        if ($viewer->isSuperAdmin()) {
            $tenantCode = (string) Tenant::query()->whereKey($tenantId)->value('code');
            if ($tenantCode !== '') {
                return redirect()->route('tenant-users.index', ['tenant' => $tenantCode]);
            }
        }

        return redirect()->route('tenant-users.index');
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

    private function resolveTenantScope(Request $request, User $viewer, $availableTenants): int
    {
        if (! $viewer->isSuperAdmin()) {
            $this->ensureTenantScopeNotOverridden($request, $viewer);
            $requestedTenantCode = trim((string) $request->query('tenant', ''));
            if ($requestedTenantCode !== '') {
                $tenant = Tenant::query()->whereRaw('LOWER(code) = ?', [strtolower($requestedTenantCode)])->first(['id', 'code']);
                if (! $tenant || (int) $tenant->id !== (int) $viewer->tenant_id) {
                    abort(403);
                }
            }

            return (int) ($viewer->tenant_id ?? 0);
        }

        $requestedTenantCode = trim((string) $request->query('tenant', ''));
        if ($requestedTenantCode !== '') {
            $tenant = $availableTenants->first(
                fn ($candidate) => strtolower((string) $candidate->code) === strtolower($requestedTenantCode)
            );
            if (! $tenant) {
                abort(404);
            }

            return (int) $tenant->id;
        }

        return (int) optional($availableTenants->first())->id;
    }

    private function ensureTenantScopeNotOverridden(Request $request, User $viewer): void
    {
        if ($viewer->isSuperAdmin()) {
            return;
        }

        if ($request->has('tenant_id') || $request->has('tenant_code')) {
            abort(403);
        }
    }
}
