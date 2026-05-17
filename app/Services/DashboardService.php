<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function summary(User $viewer, ?int $tenantId = null): array
    {
        $now = now();
        $base = $this->scopedBookingsQuery($viewer, $tenantId);
        $upcoming = (clone $base)->where('tour_start_at', '>=', $now);

        return [
            'total_bookings' => (clone $base)->count(),
            'upcoming_tours' => (clone $upcoming)->count(),
            'guests_expected' => (clone $upcoming)->sum('participants'),
            'needs_attention' => (clone $base)
                ->whereBetween('tour_start_at', [$now, Carbon::now()->addDay()])
                ->count(),
            'gross_sales' => round((float) (clone $base)->where('status', 'confirmed')->sum('gross_amount'), 2),
            'net_revenue' => round((float) (clone $base)->where('status', 'confirmed')->sum('net_amount'), 2),
            'revenue_idr' => round((float) (clone $base)->where('status', 'confirmed')->sum('revenue_amount'), 2),
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
        $tenantQuery = Tenant::query();
        if ($tenantId !== null) {
            $tenantQuery->whereKey($tenantId);
        }

        $userQuery = User::query()->whereRaw('LOWER(COALESCE(role, \'\')) != ?', ['superadmin']);
        if ($tenantId !== null) {
            $userQuery->where('tenant_id', $tenantId);
        }

        $otaBookingsQuery = Booking::query();
        if ($tenantId !== null) {
            $otaBookingsQuery->where('tenant_id', $tenantId);
        }
        $otaBookingsQuery->where(function ($query): void {
            $query->where('booking_source', 'ota')
                ->orWhere(function ($otaChannel): void {
                    $otaChannel->whereNotNull('channel')
                        ->whereRaw("LOWER(channel) NOT IN ('manual', 'direct', '')");
                });
        });

        $gygConnectionsQuery = TenantTravelAgentConnection::query()
            ->where('status', 'connected')
            ->whereHas('travelAgent', function ($query): void {
                $query->whereRaw('LOWER(code) = ?', ['getyourguide']);
            });
        if ($tenantId !== null) {
            $gygConnectionsQuery->where('tenant_id', $tenantId);
        }

        return [
            'total_tenants' => (clone $tenantQuery)->count(),
            'active_tenants' => (clone $tenantQuery)->where('is_active', true)->count(),
            'total_users' => (clone $userQuery)->count(),
            'ota_bookings' => (clone $otaBookingsQuery)->count(),
            'gyg_connections' => (clone $gygConnectionsQuery)->count(),
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

        $bookings = $this->scopedBookingsQuery($viewer, $tenantId)
            ->where(function ($query): void {
                $query->where('booking_source', 'ota')
                    ->orWhere(function ($otaChannel): void {
                        $otaChannel->whereNotNull('channel')
                            ->whereRaw("LOWER(channel) NOT IN ('manual', 'direct', '')");
                    });
            })
            ->with('customer:id,country_code')
            ->get(['id', 'channel', 'participants', 'customer_id']);

        foreach ($bookings as $booking) {
            $channel = $this->normalizeOtaChannel($booking->channel);
            if ($channel === null) {
                continue;
            }

            $participants = max(0, (int) $booking->participants);
            $countryCode = strtoupper((string) ($booking->customer?->country_code ?? ''));

            match ($channel) {
                'getyourguide' => $this->incrementGygMarket($counts, $countryCode),
                'klook' => $counts['klook_hk']++,
                'traveloka' => $counts['traveloka_id']++,
                'viator' => $counts['viator_us']++,
                default => null,
            };

            if (array_key_exists($channel, $channelBookings)) {
                $channelBookings[$channel]++;
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

    private function incrementGygMarket(array &$counts, string $countryCode): void
    {
        if ($countryCode === 'SG') {
            $counts['gyg_sg']++;

            return;
        }

        $counts['gyg_uk']++;
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
