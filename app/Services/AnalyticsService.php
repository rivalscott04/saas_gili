<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService
{
    public function overview(User $viewer): array
    {
        $bookingStats = $viewer->bookingsVisibleQuery()
            ->selectRaw(
                "COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                SUM(CASE WHEN needs_attention = 1 THEN 1 ELSE 0 END) as needs_attention"
            )
            ->first();

        $totalBookings = (int) ($bookingStats->total_bookings ?? 0);
        $confirmedBookings = (int) ($bookingStats->confirmed_bookings ?? 0);
        $needsAttention = (int) ($bookingStats->needs_attention ?? 0);

        $customerScope = $this->scopedCustomerQuery($viewer);
        $totalCustomers = (clone $customerScope)->count();

        $repeatCustomers = (clone $customerScope)
            ->whereHas('bookings', function ($q) use ($viewer): void {
                $q->visibleToUser($viewer);
            }, '>', 1)
            ->count();

        $statusBreakdown = $viewer->bookingsVisibleQuery()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $sourceBreakdown = (clone $customerScope)
            ->whereHas('bookings', function ($q) use ($viewer): void {
                $q->visibleToUser($viewer);
            })
            ->select('external_source', DB::raw('COUNT(*) as total'))
            ->groupBy('external_source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->external_source,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'total_customers' => $totalCustomers,
            'total_bookings' => $totalBookings,
            'confirmed_rate' => $totalBookings > 0 ? round(($confirmedBookings / $totalBookings) * 100, 2) : 0,
            'attention_rate' => $totalBookings > 0 ? round(($needsAttention / $totalBookings) * 100, 2) : 0,
            'repeat_customer_rate' => $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 2) : 0,
            'status_breakdown' => $statusBreakdown,
            'source_breakdown' => $sourceBreakdown,
            'top_tags' => $this->topTags($viewer),
        ];
    }

    public function trends(User $viewer, string $period = 'weekly'): array
    {
        $isMonthly = $period === 'monthly';
        $points = $isMonthly ? 6 : 8;
        $start = $isMonthly
            ? now()->startOfMonth()->subMonths($points - 1)
            : now()->startOfWeek()->subWeeks($points - 1);
        $end = now()->endOfDay();

        $labels = collect(CarbonPeriod::create(
            $start,
            $isMonthly ? '1 month' : '1 week',
            $isMonthly ? now()->startOfMonth() : now()->startOfWeek()
        ))->map(function (Carbon $date) use ($isMonthly): string {
            return $isMonthly ? $date->format('M Y') : 'W'.$date->format('W');
        })->values();

        $bucketExpr = $this->trendBucketSql($isMonthly);
        $volumeByBucket = $viewer->bookingsVisibleQuery()
            ->whereBetween('tour_start_at', [$start, $end])
            ->selectRaw("{$bucketExpr} as bucket, COUNT(*) as total, SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed, SUM(CASE WHEN needs_attention = 1 THEN 1 ELSE 0 END) as needs_attention")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $responseByBucket = $this->averageResponseMinutesByBucket($viewer, $start, $end, $isMonthly);

        $trend = $labels->map(function (string $label) use ($volumeByBucket, $responseByBucket): array {
            $row = $volumeByBucket->get($label);
            $total = (int) ($row->total ?? 0);
            $confirmed = (int) ($row->confirmed ?? 0);
            $needsAttention = (int) ($row->needs_attention ?? 0);
            $avgResponse = (float) ($responseByBucket[$label] ?? 0);

            return [
                'label' => $label,
                'confirmed_rate' => $total > 0 ? round(($confirmed / $total) * 100, 2) : 0,
                'attention_rate' => $total > 0 ? round(($needsAttention / $total) * 100, 2) : 0,
                'avg_response_minutes' => round($avgResponse, 2),
                'volume' => $total,
            ];
        })->all();

        $standbyNow = (clone $viewer->bookingsVisibleQuery())->where('status', 'standby')->count();

        $standbyToConfirmedQuery = DB::table('booking_status_events')
            ->join('bookings', 'bookings.id', '=', 'booking_status_events.booking_id')
            ->where('booking_status_events.old_status', 'standby')
            ->where('booking_status_events.new_status', 'confirmed');

        $this->applyBookingVisibilityToQuery($standbyToConfirmedQuery, $viewer, 'bookings');

        $standbyToConfirmed = $standbyToConfirmedQuery->count();
        $funnelBase = $standbyNow + $standbyToConfirmed;

        return [
            'period' => $isMonthly ? 'monthly' : 'weekly',
            'trend' => $trend,
            'funnel' => [
                'standby_now' => $standbyNow,
                'standby_to_confirmed' => $standbyToConfirmed,
                'conversion_rate' => $funnelBase > 0 ? round(($standbyToConfirmed / $funnelBase) * 100, 2) : 0,
            ],
        ];
    }

    public function exportBookingsCsv(User $viewer): StreamedResponse
    {
        $rows = $viewer->bookingsVisibleQuery()
            ->with('customer')
            ->orderByDesc('tour_start_at')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'booking_id',
                'tour_name',
                'customer_name',
                'customer_email',
                'customer_phone',
                'tour_start_at_utc',
                'status',
                'participants',
                'assigned_to_name',
                'needs_attention',
                'tags',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->tour_name,
                    $row->customer?->full_name ?? $row->customer_name,
                    $row->customer?->email ?? $row->customer_email,
                    $row->customer?->phone ?? $row->customer_phone,
                    $row->tour_start_at?->toISOString(),
                    $row->status,
                    $row->participants,
                    $row->assigned_to_name,
                    $row->needs_attention ? 'yes' : 'no',
                    implode('|', $row->tags ?? []),
                ]);
            }
            fclose($out);
        }, 'bookings-guide-export.csv', ['Content-Type' => 'text/csv']);
    }

    private function scopedCustomerQuery(User $viewer): Builder
    {
        return Customer::query()
            ->whereHas('bookings', function ($q) use ($viewer): void {
                $q->visibleToUser($viewer);
            })
            ->when(! $viewer->isSuperAdmin(), function ($q) use ($viewer): void {
                $q->where(function ($tenantScope) use ($viewer): void {
                    $tenantScope->where('tenant_id', $viewer->tenant_id)->orWhereNull('tenant_id');
                });
            });
    }

    private function trendBucketSql(bool $isMonthly): string
    {
        if ($isMonthly) {
            return DB::connection()->getDriverName() === 'sqlite'
                ? "strftime('%b %Y', tour_start_at)"
                : "DATE_FORMAT(tour_start_at, '%b %Y')";
        }

        return DB::connection()->getDriverName() === 'sqlite'
            ? "('W' || printf('%02d', cast(strftime('%W', tour_start_at) as integer)))"
            : "CONCAT('W', LPAD(WEEK(tour_start_at, 3), 2, '0'))";
    }

    /**
     * @return array<string, float>
     */
    private function averageResponseMinutesByBucket(User $viewer, Carbon $start, Carbon $end, bool $isMonthly): array
    {
        $bucketExpr = $this->trendBucketSql($isMonthly);
        $bucketExpr = str_replace('tour_start_at', 'b.tour_start_at', $bucketExpr);

        $chatFirsts = DB::table('chat_messages')
            ->selectRaw("booking_id,
                MIN(CASE WHEN sender = 'customer' THEN created_at END) as first_customer,
                MIN(CASE WHEN sender = 'operator' THEN created_at END) as first_operator")
            ->groupBy('booking_id');

        $query = DB::table('bookings as b')
            ->joinSub($chatFirsts, 'cf', 'b.id', '=', 'cf.booking_id')
            ->whereBetween('b.tour_start_at', [$start, $end])
            ->whereNotNull('cf.first_customer')
            ->whereNotNull('cf.first_operator')
            ->whereColumn('cf.first_operator', '>', 'cf.first_customer')
            ->selectRaw("{$bucketExpr} as bucket")
            ->selectRaw($this->responseMinutesAverageSql().' as avg_response');

        $this->applyBookingVisibilityToQuery($query, $viewer, 'b');

        return $query
            ->groupBy('bucket')
            ->pluck('avg_response', 'bucket')
            ->map(fn ($value): float => (float) $value)
            ->all();
    }

    private function responseMinutesAverageSql(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? 'AVG((julianday(cf.first_operator) - julianday(cf.first_customer)) * 1440.0)'
            : 'AVG(TIMESTAMPDIFF(MINUTE, cf.first_customer, cf.first_operator))';
    }

    private function applyBookingVisibilityToQuery($query, User $viewer, string $table = 'bookings'): void
    {
        if ($viewer->isSuperAdmin()) {
            return;
        }

        $query->where(function ($tenantScope) use ($viewer, $table): void {
            $tenantScope->where("{$table}.tenant_id", $viewer->tenant_id)
                ->orWhereNull("{$table}.tenant_id");
        });

        if ($viewer->isGuide()) {
            $query->where(function ($guideScope) use ($viewer, $table): void {
                $guideScope->where("{$table}.user_id", $viewer->id)
                    ->orWhereNull("{$table}.user_id");
            });
        }
    }

    private function topTags(User $viewer): array
    {
        $tags = $viewer->bookingsVisibleQuery()
            ->whereNotNull('tags')
            ->orderByDesc('id')
            ->limit(5000)
            ->pluck('tags')
            ->flatten()
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn ($tag) => strtolower(trim($tag)))
            ->values();

        return $tags
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->map(fn ($count, $tag) => ['tag' => $tag, 'total' => $count])
            ->values()
            ->all();
    }
}
