<?php

namespace App\Http\Controllers;

use App\Models\TenantOnboardingState;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private readonly OnboardingService $service)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTenantAdmin(), 403);

        $tenant = $user->tenant;
        abort_if($tenant === null, 403);

        // Catat (idempotent) timestamp tiap step yang sudah done — pure derivation
        // tetap dipakai untuk render, ini hanya untuk audit & analitik nantinya.
        $this->service->snapshotCompletedSteps($tenant);

        $steps = $this->service->summaryFor($tenant);

        return view('onboarding.checklist', [
            'tenant' => $tenant,
            'steps' => $steps,
            'mandatoryDone' => $this->service->mandatoryCompleted($tenant),
            'mandatoryTotal' => $this->service->mandatoryTotal(),
            'mode' => $tenant->onboardingState?->mode ?? TenantOnboardingState::MODE_TWO_WAY_SYNC,
            'modeIsSet' => $tenant->onboardingState?->mode !== null,
            'isDismissed' => $tenant->onboardingState?->dismissed_at !== null,
            'isAllMandatoryDone' => $this->service->isAllMandatoryDone($tenant),
        ]);
    }

    public function setMode(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTenantAdmin(), 403);

        $validated = $request->validate([
            'mode' => 'required|in:' . implode(',', TenantOnboardingState::ALLOWED_MODES),
        ]);

        $this->service->setMode($user->tenant, $validated['mode']);

        return redirect()->route('onboarding.index')
            ->with('status', __('translation.onboarding-mode-saved'));
    }

    public function dismiss(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTenantAdmin(), 403);

        $this->service->dismiss($user->tenant);

        return redirect('/dashboard-analytics')
            ->with('status', __('translation.onboarding-dismissed-flash'));
    }
}
