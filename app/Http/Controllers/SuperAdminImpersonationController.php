<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SuperAdminImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SuperAdminImpersonationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! SuperAdminImpersonation::isEnabled()) {
            abort(404);
        }

        $actor = $request->user();
        if (! $actor || ! $actor->isSuperAdmin()) {
            abort(403);
        }

        if (SuperAdminImpersonation::isImpersonating()) {
            return redirect()->route('root');
        }

        $users = User::query()
            ->whereRaw('LOWER(COALESCE(role, \'\')) != ?', ['superadmin'])
            ->with('tenant:id,name,code')
            ->orderBy('tenant_id')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        return view('superadmin-impersonation', [
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! SuperAdminImpersonation::isEnabled()) {
            abort(404);
        }

        $actor = $request->user();
        if (! $actor || ! $actor->isSuperAdmin()) {
            abort(403);
        }

        if (SuperAdminImpersonation::isImpersonating()) {
            abort(403);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::query()->findOrFail((int) $data['user_id']);
        if ($target->isSuperAdmin()) {
            abort(403);
        }

        $request->session()->put(SuperAdminImpersonation::SESSION_KEY, $actor->id);
        Auth::login($target, false);
        $request->session()->regenerate();

        return redirect()->route('root')->with('system_alert', [
            'icon' => 'info',
            'title' => __('translation.superadmin-impersonate-started-title'),
            'message' => __('translation.superadmin-impersonate-started-body', [
                'name' => $target->name,
                'email' => $target->email,
            ]),
        ]);
    }

    public function leave(Request $request): RedirectResponse
    {
        $impersonatorId = (int) $request->session()->get(SuperAdminImpersonation::SESSION_KEY, 0);
        if ($impersonatorId <= 0) {
            abort(403);
        }

        $impersonator = User::query()->find($impersonatorId);
        $request->session()->forget(SuperAdminImpersonation::SESSION_KEY);

        if (! $impersonator || ! $impersonator->isSuperAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('system_alert', [
                'icon' => 'warning',
                'title' => __('translation.superadmin-impersonate-invalid-session-title'),
                'message' => __('translation.superadmin-impersonate-invalid-session-body'),
            ]);
        }

        Auth::login($impersonator, false);
        $request->session()->regenerate();

        if (SuperAdminImpersonation::isEnabled()) {
            return redirect()->route('superadmin.impersonation.index')->with('system_alert', [
                'icon' => 'success',
                'title' => __('translation.superadmin-impersonate-ended-title'),
                'message' => __('translation.superadmin-impersonate-ended-body'),
            ]);
        }

        return redirect()->route('root')->with('system_alert', [
            'icon' => 'success',
            'title' => __('translation.superadmin-impersonate-ended-title'),
            'message' => __('translation.superadmin-impersonate-ended-body'),
        ]);
    }
}
