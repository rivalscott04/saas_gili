<?php

namespace App\Http\Middleware;

use App\Services\OnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks tenant_admin from app routes until mandatory onboarding steps are done.
 * Dismiss only hides dashboard/sidebar widgets — it does not bypass this gate.
 */
class EnsureTenantOnboardingGate
{
    public function __construct(private readonly OnboardingService $onboarding)
    {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $this->onboarding->shouldForceRedirect($user)) {
            return $next($request);
        }

        if (OnboardingService::isRouteAllowedWhileGated(
            $request->route()?->getName(),
            $request->path(),
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Complete mandatory onboarding steps first.');
        }

        return redirect()
            ->route('onboarding.index')
            ->with('status', __('translation.onboarding-gate-redirect'));
    }
}
