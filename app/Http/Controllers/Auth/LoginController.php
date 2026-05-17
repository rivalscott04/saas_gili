<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\PolicyException;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\OnboardingService;
use App\Services\UserAccessLogService;
use App\Support\AccessAlert;
use App\Support\SuperAdminImpersonation;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function showLoginForm(Request $request)
    {
        // Stale impersonation marker (e.g. after session partial loss) breaks guest pages / CSRF expectations.
        $request->session()->forget(SuperAdminImpersonation::SESSION_KEY);

        // Login flow tidak boleh terikat dengan pemilihan paket landing. Plan selection hanya relevan
        // saat user mendaftar dari pricing card. Buang sisa state supaya banner aktivasi paket tidak
        // muncul ulang setelah user login (apalagi superadmin).
        $request->session()->forget('selected_landing_plan_code');

        return view('auth.login');
    }

    protected function redirectTo(): string
    {
        $user = auth()->user();

        if (! $user) {
            return RouteServiceProvider::HOME;
        }

        // Tenant_admin baru yang belum selesai onboarding mandatory dialihkan ke
        // /onboarding alih-alih dashboard (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §4.4 + §8.2).
        if (app(OnboardingService::class)->shouldForceRedirect($user)) {
            return '/onboarding';
        }

        return '/dashboard-analytics';
    }

    protected function authenticated($request, $user): ?RedirectResponse
    {
        if ($user->isSuspended()) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw PolicyException::forbidden(
                AccessAlert::REASON_USER_SUSPENDED,
                'User access is suspended.',
            );
        }

        if ($user->hasExpiredSubscription()) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw PolicyException::forbidden(
                AccessAlert::REASON_SUBSCRIPTION_EXPIRED,
                'Subscription is no longer active.',
            );
        }

        // Pastikan tidak ada state plan landing yang nyangkut di session, supaya halaman dashboard
        // tidak menampilkan modal "Plan dipilih" untuk user yang hanya login (mis. superadmin).
        $request->session()->forget('selected_landing_plan_code');

        app(UserAccessLogService::class)->recordFromRequest($user, $request);

        return null;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
