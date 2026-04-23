<?php

namespace App\Providers;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Model::preventLazyLoading(! app()->isProduction());

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api-sensitive', function (Request $request): Limit {
            $key = $request->user() !== null
                ? 'user:'.$request->user()->getAuthIdentifier()
                : 'ip:'.$request->ip();

            return Limit::perMinute(240)->by($key);
        });

        RateLimiter::for('magic-link', function (Request $request): array {
            // Named throttles run before SubstituteBindings (framework middleware priority), so
            // {booking} is usually not a model yet — derive the id from the path when needed.
            $route = $request->route();
            $bookingKey = 'unknown';
            if ($route !== null) {
                try {
                    $original = $route->originalParameter('booking');
                    if (is_scalar($original) && $original !== '') {
                        $bookingKey = (string) $original;
                    }
                } catch (\LogicException) {
                    // Route not bound yet.
                }
                if ($bookingKey === 'unknown') {
                    try {
                        $resolved = $route->parameter('booking');
                        if ($resolved instanceof Booking) {
                            $bookingKey = (string) $resolved->getKey();
                        } elseif (is_scalar($resolved)) {
                            $bookingKey = (string) $resolved;
                        }
                    } catch (\LogicException) {
                        // Parameters not available yet.
                    }
                }
            }
            if ($bookingKey === 'unknown' && preg_match('#^api/v1/bookings/([^/]+)/magic-link#', $request->path(), $matches)) {
                $bookingKey = $matches[1];
            }

            $perBookingIp = max(1, (int) config('rate_limit.magic_link_per_booking_ip', 20));
            $perIp = max(1, (int) config('rate_limit.magic_link_per_ip', 100));

            return [
                Limit::perMinute($perBookingIp)->by('ml-booking:'.$bookingKey.':'.$request->ip()),
                Limit::perMinute($perIp)->by('ml-ip:'.$request->ip()),
            ];
        });
    }
}
