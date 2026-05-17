<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class LandingPricingCache
{
    public const PUBLIC_PLANS_KEY = 'landing.pricing.plans.v1';

    public const ADMIN_PLANS_KEY = 'admin.landing.pricing.plans.v1';

    public const ADMIN_CATEGORIES_KEY = 'admin.landing.categories.active.v1';

    public const REGISTER_PLANS_KEY = 'register.pricing.plans.v1';

    public static function flush(): void
    {
        foreach ([
            self::PUBLIC_PLANS_KEY,
            self::ADMIN_PLANS_KEY,
            self::ADMIN_CATEGORIES_KEY,
            self::REGISTER_PLANS_KEY,
        ] as $key) {
            Cache::forget($key);
        }
    }

    public static function ttlSeconds(): int
    {
        return max(60, (int) config('performance.landing_pricing_cache_seconds', 600));
    }
}
