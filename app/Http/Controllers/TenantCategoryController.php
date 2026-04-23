<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantWebScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantCategoryController extends Controller
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

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $selectedIds = $tenant->categories()->pluck('categories.id')->all();

        return view('apps-tenant-categories', [
            'tenant' => $tenant,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedIds,
            'availableTenants' => $availableTenants,
            'showTenantSwitcher' => $viewer->isSuperAdmin(),
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
                'tenant_code' => ['required', 'string', Rule::exists('tenants', 'code')],
            ]);
        }

        $tenantId = TenantWebScope::resolveTenantIdFromPayload($viewer, $request->all());
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }

        $rules = [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('is_active', true)],
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string'];
        } else {
            $rules['tenant_code'] = ['prohibited'];
        }
        $payload = $request->validate($rules);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $tenant->categories()->sync($payload['category_ids']);

        return $this->redirectToIndex($tenantId, $viewer)->with('system_alert', [
            'icon' => 'success',
            'title' => 'Kategori tenant diperbarui',
            'message' => 'Segmentasi tenant sudah disimpan.',
        ]);
    }

    private function redirectToIndex(int $tenantId, User $viewer): RedirectResponse
    {
        if ($viewer->isSuperAdmin()) {
            $tenantCode = (string) Tenant::query()->whereKey($tenantId)->value('code');
            if ($tenantCode !== '') {
                return redirect()->route('tenant-categories.index', ['tenant' => $tenantCode]);
            }
        }

        return redirect()->route('tenant-categories.index');
    }
}
