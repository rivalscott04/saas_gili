<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IP geolocation (dashboard "Live users by country")
    |--------------------------------------------------------------------------
    |
    | Uses ip-api.com (free, non-commercial). Results are cached per IP.
    | Private/local IPs fall back to defaults below (useful in local dev).
    |
    */
    'provider_url' => env('GEOLOCATION_PROVIDER_URL', 'http://ip-api.com/json/{ip}?fields=status,country,countryCode,lat,lon,city,regionName'),

    'timeout_seconds' => (int) env('GEOLOCATION_TIMEOUT', 3),

    'cache_ttl_seconds' => (int) env('GEOLOCATION_CACHE_TTL', 86400),

    'local_fallback' => [
        'country_code' => env('GEOLOCATION_LOCAL_COUNTRY_CODE', 'ID'),
        'country_name' => env('GEOLOCATION_LOCAL_COUNTRY_NAME', 'Indonesia'),
        'city' => env('GEOLOCATION_LOCAL_CITY', 'Local'),
        'region' => null,
        'latitude' => (float) env('GEOLOCATION_LOCAL_LAT', -8.565),
        'longitude' => (float) env('GEOLOCATION_LOCAL_LON', 116.351),
    ],

    /** Do not log more than once per user+IP within this many minutes. */
    'log_throttle_minutes' => (int) env('GEOLOCATION_LOG_THROTTLE_MINUTES', 360),

    /** Dashboard aggregates access logs from the last N days. */
    'dashboard_lookback_days' => (int) env('GEOLOCATION_DASHBOARD_LOOKBACK_DAYS', 30),

    /** Cache TTL for superadmin live-users map aggregate (seconds). */
    'dashboard_cache_seconds' => (int) env('GEOLOCATION_DASHBOARD_CACHE_SECONDS', 300),
];
