<?php

namespace App\View\Composers;

use App\Services\OnboardingService;
use Illuminate\View\View;

class OnboardingComposer
{
    public function __construct(private readonly OnboardingService $onboarding)
    {
    }

    public function compose(View $view): void
    {
        $defaults = [
            'onboardingShowNavLink' => false,
            'onboardingShowDashboardWidget' => false,
            'onboardingMandatoryDone' => 0,
            'onboardingMandatoryTotal' => 0,
        ];

        $user = auth()->user();
        if ($user === null || ! $user->isTenantAdmin() || $user->tenant === null) {
            $view->with($defaults);

            return;
        }

        $state = $this->onboarding->uiStateFor($user->tenant);

        $view->with([
            'onboardingShowNavLink' => $state['show_nav_link'],
            'onboardingShowDashboardWidget' => $state['show_dashboard_widget'],
            'onboardingMandatoryDone' => $state['mandatory_done'],
            'onboardingMandatoryTotal' => $state['mandatory_total'],
        ]);
    }
}
