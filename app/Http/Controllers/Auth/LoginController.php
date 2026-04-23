<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\PolicyException;
use App\Http\Controllers\Controller;
use App\Models\LandingPricingPlan;
use App\Providers\RouteServiceProvider;
use App\Support\AccessAlert;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
        $selectedPlan = $this->resolveSelectedPlan($request);

        return view('auth.login', [
            'selectedPlan' => $selectedPlan,
            'selectedPlanCode' => $selectedPlan?->code,
        ]);
    }

    protected function redirectTo(): string
    {
        $user = auth()->user();

        if (! $user) {
            return RouteServiceProvider::HOME;
        }

        return $user->isAdmin()
            ? '/dashboard-analytics'
            : '/dashboard-analytics';
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

        $selectedPlan = $this->resolveSelectedPlan($request);
        if ($selectedPlan !== null) {
            $request->session()->flash('system_alert', [
                'icon' => 'info',
                'title' => 'Plan dipilih',
                'message' => 'Kamu memilih paket '.$selectedPlan->name.'. Tim bisa lanjutkan aktivasi paket setelah login.',
            ]);
        }

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

    private function resolveSelectedPlan(Request $request): ?LandingPricingPlan
    {
        $incomingCode = strtolower(trim((string) ($request->query('plan') ?? $request->input('selected_plan_code', ''))));
        $sessionCode = strtolower(trim((string) $request->session()->get('selected_landing_plan_code', '')));
        $planCode = $incomingCode !== '' ? $incomingCode : $sessionCode;

        if ($planCode === '') {
            $request->session()->forget('selected_landing_plan_code');

            return null;
        }

        $plan = LandingPricingPlan::query()
            ->where('code', $planCode)
            ->first(['id', 'code', 'name', 'price_monthly', 'price_yearly', 'is_popular']);

        if ($plan === null) {
            $request->session()->forget('selected_landing_plan_code');

            return null;
        }

        $request->session()->put('selected_landing_plan_code', $plan->code);

        return $plan;
    }
}
