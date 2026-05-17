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

        $steps = $this->service->summaryFor($tenant);

        // Catat (idempotent) timestamp tiap step yang sudah done — memakai cache summaryFor().
        $this->service->snapshotCompletedSteps($tenant);

        $mandatoryDone = 0;
        foreach ($steps as $step) {
            if ($step['mandatory'] && $step['done']) {
                $mandatoryDone++;
            }
        }
        $mandatoryTotal = $this->service->mandatoryTotal();

        return view('onboarding.checklist', [
            'tenant' => $tenant,
            'steps' => $steps,
            'mandatoryDone' => $mandatoryDone,
            'mandatoryTotal' => $mandatoryTotal,
            'mode' => $tenant->onboardingState?->mode ?? TenantOnboardingState::MODE_TWO_WAY_SYNC,
            'modeIsSet' => $tenant->onboardingState?->mode !== null,
            'isDismissed' => $tenant->onboardingState?->dismissed_at !== null,
            'isAllMandatoryDone' => $mandatoryDone >= $mandatoryTotal,
        ]);
    }

    public function setMode(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTenantAdmin(), 403);

        $validated = $request->validate([
            'mode' => 'required|in:'.implode(',', TenantOnboardingState::ALLOWED_MODES),
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
