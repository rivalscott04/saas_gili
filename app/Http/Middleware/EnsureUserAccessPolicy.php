<?php

namespace App\Http\Middleware;

use App\Exceptions\PolicyException;
use App\Support\AccessAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccessPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw PolicyException::forbidden(
                AccessAlert::REASON_USER_SUSPENDED,
                'User access is suspended.',
            );
        }

        if ($user->hasExpiredSubscription()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw PolicyException::forbidden(
                AccessAlert::REASON_SUBSCRIPTION_EXPIRED,
                'Subscription is no longer active.',
            );
        }

        if ($user->isSeatLimitReached() && ! $request->session()->has('seat_limit_notified_at')) {
            $request->session()->flash('system_alert', AccessAlert::fromReason(AccessAlert::REASON_SEAT_LIMIT_REACHED));
            $request->session()->put('seat_limit_notified_at', now()->toIso8601String());
        }

        if (! $request->is('api/*') && $request->has('tenant_id')) {
            abort(403);
        }

        return $next($request);
    }
}
