<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserAccessLogService
{
    public function __construct(private readonly IpGeolocationService $geolocation)
    {
    }

    public function recordFromRequest(User $user, Request $request): void
    {
        $ip = (string) $request->ip();
        if ($ip === '') {
            return;
        }

        $this->recordAccess($user, $ip, Str::limit((string) $request->userAgent(), 500, ''));
    }

    public function recordAccess(User $user, string $ipAddress, ?string $userAgent = null): void
    {
        if (! Schema::hasTable('user_access_logs')) {
            return;
        }

        $ip = trim($ipAddress);
        if ($ip === '') {
            return;
        }

        if ($this->wasRecentlyRecorded((int) $user->id, $ip)) {
            return;
        }

        $location = $this->geolocation->resolve($ip);
        if ($location === null) {
            return;
        }

        UserAccessLog::query()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'ip_address' => $ip,
            'country_code' => $location['country_code'],
            'country_name' => $location['country_name'],
            'region' => $location['region'],
            'city' => $location['city'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'user_agent' => $userAgent,
            'accessed_at' => now(),
        ]);

        $this->markRecorded((int) $user->id, $ip);
    }

    public function wasRecentlyRecorded(int $userId, string $ipAddress): bool
    {
        return Cache::has($this->throttleCacheKey($userId, $ipAddress));
    }

    public function markRecorded(int $userId, string $ipAddress): void
    {
        $minutes = max(1, (int) config('geolocation.log_throttle_minutes', 360));
        Cache::put($this->throttleCacheKey($userId, $ipAddress), true, now()->addMinutes($minutes));
    }

    /**
     * Live users by country for the dashboard map (from stored IP lookups).
     *
     * @return array{
     *     markers: list<array{name: string, coords: array{0: float, 1: float}}>,
     *     rows: list<array{country: string, sessions: int, users: int}>,
     *     uses_live_data: bool
     * }
     */
    public function liveUsersByCountry(?int $tenantId = null, ?int $lookbackDays = null): array
    {
        $days = $lookbackDays ?? (int) config('geolocation.dashboard_lookback_days', 30);
        $cacheKey = 'dashboard.live_users.'.($tenantId ?? 'all').'.'.$days;
        $cacheSeconds = max(60, (int) config('geolocation.dashboard_cache_seconds', 300));

        return Cache::remember($cacheKey, $cacheSeconds, function () use ($tenantId, $days): array {
            return $this->buildLiveUsersByCountry($tenantId, $days);
        });
    }

    /**
     * @return array{
     *     markers: list<array{name: string, coords: array{0: float, 1: float}}>,
     *     rows: list<array{country: string, sessions: int, users: int}>,
     *     uses_live_data: bool
     * }
     */
    private function buildLiveUsersByCountry(?int $tenantId, int $days): array
    {
        if (! Schema::hasTable('user_access_logs')) {
            return [
                'markers' => [],
                'rows' => [],
                'uses_live_data' => false,
            ];
        }

        $since = Carbon::now()->subDays(max(1, $days));

        $query = UserAccessLog::query()
            ->where('accessed_at', '>=', $since)
            ->whereNotNull('country_code');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $aggregates = $query
            ->selectRaw('country_code, MAX(country_name) as country_name, AVG(latitude) as latitude, AVG(longitude) as longitude, COUNT(*) as sessions, COUNT(DISTINCT user_id) as users')
            ->groupBy('country_code')
            ->orderByDesc('sessions')
            ->get();

        $usesLiveData = $aggregates->isNotEmpty();

        $markers = $aggregates
            ->filter(fn ($row): bool => $row->latitude !== null && $row->longitude !== null)
            ->map(function ($row): array {
                $name = (string) ($row->country_name ?: $row->country_code);

                return [
                    'name' => $name,
                    'coords' => [
                        round((float) $row->latitude, 4),
                        round((float) $row->longitude, 4),
                    ],
                ];
            })
            ->values()
            ->all();

        $rows = $aggregates->map(fn ($row): array => [
            'country' => (string) ($row->country_name ?: $row->country_code),
            'sessions' => (int) $row->sessions,
            'users' => (int) $row->users,
        ])->values()->all();

        return [
            'markers' => $markers,
            'rows' => $rows,
            'uses_live_data' => $usesLiveData,
        ];
    }

    private function throttleCacheKey(int $userId, string $ipAddress): string
    {
        return 'access_log_throttle:'.$userId.':'.sha1($ipAddress);
    }
}
