<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminTenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isSuperAdmin()) {
            return redirect()->route('root');
        }

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $tenantQuery = Tenant::query()->orderBy('name');
        if ($q !== '') {
            $tenantQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            });
        }
        if ($status === 'active') {
            $tenantQuery->where('is_active', true);
        } elseif ($status === 'inactive') {
            $tenantQuery->where('is_active', false);
        }

        $tenants = $tenantQuery->paginate(15)->withQueryString();

        return view('apps-superadmin-tenants', [
            'tenants' => $tenants,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isSuperAdmin()) {
            return redirect()->route('root');
        }

        $payload = $request->validate([
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);

        if ($tenant->code === 'default' && (string) $payload['is_active'] === '0') {
            return redirect()
                ->route('superadmin.tenants.index')
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Aksi ditolak',
                    'message' => 'Default tenant tidak boleh dinonaktifkan.',
                ]);
        }

        $tenant->is_active = (string) $payload['is_active'] === '1';
        $tenant->save();

        return redirect()
            ->route('superadmin.tenants.index')
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Status tenant diperbarui',
                'message' => 'Perubahan status tenant berhasil disimpan.',
            ]);
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isSuperAdmin()) {
            return redirect()->route('root');
        }

        if ($tenant->code === 'default') {
            return redirect()
                ->route('superadmin.tenants.index')
                ->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Aksi ditolak',
                    'message' => 'Default tenant tidak boleh dihapus.',
                ]);
        }

        $tenantName = (string) $tenant->name;
        $tenant->delete();

        return redirect()
            ->route('superadmin.tenants.index')
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Tenant dihapus',
                'message' => $tenantName !== '' ? 'Tenant "'.$tenantName.'" berhasil dihapus.' : 'Tenant berhasil dihapus.',
            ]);
    }
}

