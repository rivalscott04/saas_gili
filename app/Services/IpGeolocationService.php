<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IpGeolocationService
{
    /**
     * @return array{
     *     country_code: string,
     *     country_name: string,
     *     region: ?string,
     *     city: ?string,
     *     latitude: float,
     *     longitude: float
     * }|null
     */
    public function resolve(string $ipAddress): ?array
    {
        $ip = trim($ipAddress);
        if ($ip === '' || $this->isPrivateOrReserved($ip)) {
            return $this->localFallback();
        }

        $cacheKey = 'geolocation:ip:'.sha1($ip);
        $ttl = max(60, (int) config('geolocation.cache_ttl_seconds', 86400));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $resolved = $this->fetchFromProvider($ip);
        if ($resolved !== null) {
            Cache::put($cacheKey, $resolved, $ttl);
        }

        return $resolved;
    }

    /**
     * @return array{
     *     country_code: string,
     *     country_name: string,
     *     region: ?string,
     *     city: ?string,
     *     latitude: float,
     *     longitude: float
     * }
     */
    private function localFallback(): array
    {
        $fallback = config('geolocation.local_fallback', []);

        return [
            'country_code' => strtoupper((string) ($fallback['country_code'] ?? 'ID')),
            'country_name' => (string) ($fallback['country_name'] ?? 'Indonesia'),
            'region' => isset($fallback['region']) ? (string) $fallback['region'] : null,
            'city' => isset($fallback['city']) ? (string) $fallback['city'] : null,
            'latitude' => (float) ($fallback['latitude'] ?? -8.565),
            'longitude' => (float) ($fallback['longitude'] ?? 116.351),
        ];
    }

    /**
     * @return array{
     *     country_code: string,
     *     country_name: string,
     *     region: ?string,
     *     city: ?string,
     *     latitude: float,
     *     longitude: float
     * }|null
     */
    private function fetchFromProvider(string $ip): ?array
    {
        $template = (string) config('geolocation.provider_url');
        if ($template === '') {
            return null;
        }

        $url = str_replace('{ip}', urlencode($ip), $template);

        try {
            $response = Http::timeout((int) config('geolocation.timeout_seconds', 3))
                ->acceptJson()
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload) || ($payload['status'] ?? '') !== 'success') {
            return null;
        }

        $countryCode = strtoupper((string) ($payload['countryCode'] ?? ''));
        if ($countryCode === '') {
            return null;
        }

        return [
            'country_code' => $countryCode,
            'country_name' => (string) ($payload['country'] ?? $countryCode),
            'region' => isset($payload['regionName']) ? (string) $payload['regionName'] : null,
            'city' => isset($payload['city']) ? (string) $payload['city'] : null,
            'latitude' => (float) ($payload['lat'] ?? 0),
            'longitude' => (float) ($payload['lon'] ?? 0),
        ];
    }

    private function isPrivateOrReserved(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
