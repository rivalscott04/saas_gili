<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function summary(User $viewer, ?int $tenantId = null): array
    {
        $cacheKey = 'dashboard.summary.v1.'.$viewer->getAuthIdentifier().'.'.($tenantId ?? 'all');
        $ttl = max(30, (int) config('performance.dashboard_summary_cache_seconds', 60));

        return Cache::remember($cacheKey, $ttl, fn (): array => $this->buildSummary($viewer, $tenantId));
    }

    private function buildSummary(User $viewer, ?int $tenantId): array
    {
        $now = now();
        $attentionEnd = Carbon::now()->addDay();
        $stats = $this->scopedBookingsQuery($viewer, $tenantId)
            ->selectRaw(
                'COUNT(*) as total_bookings,
                SUM(CASE WHEN tour_start_at >= ? THEN 1 ELSE 0 END) as upcoming_tours,
                SUM(CASE WHEN tour_start_at >= ? THEN participants ELSE 0 END) as guests_expected,
                SUM(CASE WHEN tour_start_at >= ? AND tour_start_at <= ? THEN 1 ELSE 0 END) as needs_attention,
                SUM(CASE WHEN status = ? THEN gross_amount ELSE 0 END) as gross_sales,
                SUM(CASE WHEN status = ? THEN net_amount ELSE 0 END) as net_revenue,
                SUM(CASE WHEN status = ? THEN revenue_amount ELSE 0 END) as revenue_idr',
                [$now, $now, $now, $attentionEnd, 'confirmed', 'confirmed', 'confirmed']
            )
            ->first();

        return [
            'total_bookings' => (int) ($stats->total_bookings ?? 0),
            'upcoming_tours' => (int) ($stats->upcoming_tours ?? 0),
            'guests_expected' => (int) ($stats->guests_expected ?? 0),
            'needs_attention' => (int) ($stats->needs_attention ?? 0),
            'gross_sales' => round((float) ($stats->gross_sales ?? 0), 2),
            'net_revenue' => round((float) ($stats->net_revenue ?? 0), 2),
            'revenue_idr' => round((float) ($stats->revenue_idr ?? 0), 2),
        ];
    }

    public function urgentBookings(User $viewer, int $limit = 3, ?int $tenantId = null)
    {
        return $this->scopedBookingsQuery($viewer, $tenantId)
            ->with(['customer', 'tenant'])
            ->whereBetween('tour_start_at', [now(), now()->addDay()])
            ->orderBy('tour_start_at')
            ->limit($limit)
            ->get();
    }

    public function recentBookings(User $viewer, int $limit = 6, ?int $tenantId = null)
    {
        return $this->scopedBookingsQuery($viewer, $tenantId)
            ->with(['customer', 'tenant'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Platform-wide counters for the superadmin analytics strip.
     *
     * @return array{
     *     total_tenants: int,
     *     active_tenants: int,
     *     total_users: int,
     *     ota_bookings: int,
     *     gyg_connections: int
     * }
     */
    public function platformSummary(?int $tenantId = null): array
    {
        $cacheKey = 'dashboard.platform_summary.'.($tenantId ?? 'all');
        $cacheSeconds = max(60, (int) config('geolocation.dashboard_cache_seconds', 300));

        return Cache::remember($cacheKey, $cacheSeconds, fn (): array => $this->buildPlatformSummary($tenantId));
    }

    /**
     * @return array{
     *     total_tenants: int,
     *     active_tenants: int,
     *     total_users: int,
     *     ota_bookings: int,
     *     gyg_connections: int
     * }
     */
    private function buildPlatformSummary(?int $tenantId): array
    {
        $tenantStats = Tenant::query()
            ->when($tenantId !== null, fn ($query) => $query->whereKey($tenantId))
            ->selectRaw('COUNT(*) as total_tenants, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_tenants')
            ->first();

        $userCount = User::query()
            ->whereRaw('LOWER(COALESCE(role, \'\')) != ?', ['superadmin'])
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->count();

        $otaBookings = Booking::query()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where(function ($query): void {
                $query->where('booking_source', 'ota')
                    ->orWhere(function ($otaChannel): void {
                        $otaChannel->whereNotNull('channel')
                            ->whereRaw("LOWER(channel) NOT IN ('manual', 'direct', '')");
                    });
            })
            ->count();

        $gygConnections = TenantTravelAgentConnection::query()
            ->where('status', 'connected')
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('travel_agents')
                    ->whereColumn('travel_agents.id', 'tenant_travel_agent_connections.travel_agent_id')
                    ->whereRaw('LOWER(travel_agents.code) = ?', ['getyourguide']);
            })
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->count();

        return [
            'total_tenants' => (int) ($tenantStats->total_tenants ?? 0),
            'active_tenants' => (int) ($tenantStats->active_tenants ?? 0),
            'total_users' => $userCount,
            'ota_bookings' => $otaBookings,
            'gyg_connections' => $gygConnections,
        ];
    }

    /**
     * OTA market breakdown for superadmin dashboard map & bar chart (GetYourGuide UK/SG, etc.).
     *
     * @return array{
     *     hub: array{name: string, coords: array{0: float, 1: float}},
     *     markers: list<array{name: string, coords: array{0: float, 1: float}}>,
     *     lines: list<array{from: string, to: string}>,
     *     bars: list<array{label: string, value: int, highlight: bool}>,
     *     channel_rows: list<array{channel: string, bookings: int, guests: int}>,
     *     uses_live_data: bool
     * }
     */
    public function channelGeographyAnalytics(User $viewer, ?int $tenantId = null): array
    {
        $cacheKey = 'dashboard.channel_geo.v1.'.$viewer->getAuthIdentifier().'.'.($tenantId ?? 'all');
        $ttl = max(30, (int) config('performance.dashboard_summary_cache_seconds', 60));

        return Cache::remember($cacheKey, $ttl, fn (): array => $this->buildChannelGeographyAnalytics($viewer, $tenantId));
    }

    private function buildChannelGeographyAnalytics(User $viewer, ?int $tenantId): array
    {
        $counts = [
            'gyg_uk' => 0,
            'gyg_sg' => 0,
            'klook_hk' => 0,
            'traveloka_id' => 0,
            'viator_us' => 0,
        ];
        $channelKeys = ['getyourguide', 'klook', 'traveloka', 'viator'];
        $channelGuests = array_fill_keys($channelKeys, 0);
        $channelBookings = array_fill_keys($channelKeys, 0);

        $otaScope = fn ($query) => $query
            ->where(function ($inner): void {
                $inner->where('bookings.booking_source', 'ota')
                    ->orWhere(function ($otaChannel): void {
                        $otaChannel->whereNotNull('bookings.channel')
                            ->whereRaw("LOWER(bookings.channel) NOT IN ('manual', 'direct', '')");
                    });
            });

        $aggregates = $this->scopedBookingsQuery($viewer, $tenantId)
            ->tap($otaScope)
            ->leftJoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->selectRaw(
                'bookings.channel as channel, UPPER(COALESCE(customers.country_code, \'\')) as country_code, COUNT(*) as booking_count, SUM(bookings.participants) as guest_sum'
            )
            ->groupBy('bookings.channel', 'customers.country_code')
            ->get();

        foreach ($aggregates as $row) {
            $channel = $this->normalizeOtaChannel($row->channel);
            if ($channel === null) {
                continue;
            }

            $bookingCount = max(0, (int) $row->booking_count);
            $participants = max(0, (int) $row->guest_sum);
            $countryCode = strtoupper((string) ($row->country_code ?? ''));

            match ($channel) {
                'getyourguide' => $this->incrementGygMarket($counts, $countryCode, $bookingCount),
                'klook' => $counts['klook_hk'] += $bookingCount,
                'traveloka' => $counts['traveloka_id'] += $bookingCount,
                'viator' => $counts['viator_us'] += $bookingCount,
                default => null,
            };

            if (array_key_exists($channel, $channelBookings)) {
                $channelBookings[$channel] += $bookingCount;
                $channelGuests[$channel] += $participants;
            }
        }

        $usesLiveData = array_sum($counts) > 0;

        $hubName = (string) __('translation.superadmin-map-hub-indonesia');
        $hub = ['name' => $hubName, 'coords' => [-8.565, 116.351]];

        $markerUk = (string) __('translation.superadmin-map-marker-uk');
        $markerSingapore = (string) __('translation.superadmin-map-marker-singapore');
        $markerHongKong = (string) __('translation.superadmin-map-marker-hong-kong');
        $markerUs = (string) __('translation.superadmin-map-marker-us');

        $markers = [
            ['name' => $markerUk, 'coords' => [55.3781, -3.436]],
            ['name' => $markerSingapore, 'coords' => [1.3521, 103.8198]],
        ];

        if ($counts['klook_hk'] > 0) {
            $markers[] = ['name' => $markerHongKong, 'coords' => [22.3193, 114.1694]];
        }
        if ($counts['viator_us'] > 0) {
            $markers[] = ['name' => $markerUs, 'coords' => [37.0902, -95.7129]];
        }

        $markers[] = $hub;

        $lines = collect($markers)
            ->pluck('name')
            ->reject(fn (string $name): bool => $name === $hub['name'])
            ->map(fn (string $name): array => ['from' => $name, 'to' => $hub['name']])
            ->values()
            ->all();

        $bars = [
            ['label' => (string) __('translation.superadmin-market-gyg-uk'), 'value' => $counts['gyg_uk'], 'highlight' => false],
            ['label' => (string) __('translation.superadmin-market-gyg-sg'), 'value' => $counts['gyg_sg'], 'highlight' => true],
            ['label' => (string) __('translation.superadmin-market-traveloka-id'), 'value' => $counts['traveloka_id'], 'highlight' => false],
            ['label' => (string) __('translation.superadmin-market-klook-hk'), 'value' => $counts['klook_hk'], 'highlight' => false],
            ['label' => (string) __('translation.superadmin-market-viator-us'), 'value' => $counts['viator_us'], 'highlight' => false],
        ];

        $channelRows = [];
        foreach ($channelBookings as $channelKey => $bookingCount) {
            if ($bookingCount <= 0) {
                continue;
            }
            $channelRows[] = [
                'channel' => (string) __('translation.channel-'.$channelKey),
                'bookings' => $bookingCount,
                'guests' => $channelGuests[$channelKey] ?? 0,
            ];
        }

        usort($channelRows, fn (array $a, array $b): int => $b['bookings'] <=> $a['bookings']);

        return [
            'hub' => $hub,
            'markers' => $markers,
            'lines' => $lines,
            'bars' => $bars,
            'channel_rows' => $channelRows,
            'uses_live_data' => $usesLiveData,
        ];
    }

    private function incrementGygMarket(array &$counts, string $countryCode, int $bookingCount = 1): void
    {
        if ($bookingCount <= 0) {
            return;
        }

        if ($countryCode === 'SG') {
            $counts['gyg_sg'] += $bookingCount;

            return;
        }

        $counts['gyg_uk'] += $bookingCount;
    }

    private function normalizeOtaChannel(?string $channel): ?string
    {
        $normalized = strtolower(trim((string) $channel));
        if ($normalized === '' || in_array($normalized, ['manual', 'direct'], true)) {
            return null;
        }

        if (str_contains($normalized, 'getyourguide') || $normalized === 'gyg') {
            return 'getyourguide';
        }
        if (str_contains($normalized, 'klook')) {
            return 'klook';
        }
        if (str_contains($normalized, 'traveloka')) {
            return 'traveloka';
        }
        if (str_contains($normalized, 'viator')) {
            return 'viator';
        }

        return $normalized;
    }

    private function scopedBookingsQuery(User $viewer, ?int $tenantId = null)
    {
        $query = $viewer->bookingsVisibleQuery();
        if ($viewer->isSuperAdmin() && $tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }
}
