<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService
{
    public function overview(User $viewer): array
    {
        $bookings = $viewer->bookingsVisibleQuery();

        $totalBookings = (clone $bookings)->count();
        $confirmedBookings = (clone $bookings)->where('status', 'confirmed')->count();
        $needsAttention = (clone $bookings)->where('needs_attention', true)->count();

        $customerQuery = Customer::query()->whereHas('bookings', function ($q) use ($viewer): void {
            $q->visibleToUser($viewer);
        })->when(! $viewer->isSuperAdmin(), function ($q) use ($viewer): void {
            $q->where(function ($tenantScope) use ($viewer): void {
                $tenantScope->where('tenant_id', $viewer->tenant_id)->orWhereNull('tenant_id');
            });
        });

        $totalCustomers = (clone $customerQuery)->count();

        $repeatCustomers = (clone $customerQuery)
            ->whereHas('bookings', function ($q) use ($viewer): void {
                $q->visibleToUser($viewer);
            }, '>', 1)
            ->count();

        $statusBreakdown = (clone $bookings)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $sourceBreakdown = Customer::query()
            ->when(! $viewer->isSuperAdmin(), function ($q) use ($viewer): void {
                $q->where(function ($tenantScope) use ($viewer): void {
                    $tenantScope->where('tenant_id', $viewer->tenant_id)->orWhereNull('tenant_id');
                });
            })
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

        $bookings = $viewer->bookingsVisibleQuery()
            ->with(['chatMessages' => fn ($q) => $q->orderBy('created_at')])
            ->whereBetween('tour_start_at', [$start, $end])
            ->get();

        $labels = collect(CarbonPeriod::create(
            $start,
            $isMonthly ? '1 month' : '1 week',
            $isMonthly ? now()->startOfMonth() : now()->startOfWeek()
        ))->map(function (Carbon $date) use ($isMonthly): string {
            return $isMonthly ? $date->format('M Y') : 'W'.$date->format('W');
        })->values();

        $series = $labels->mapWithKeys(fn (string $label) => [
            $label => [
                'label' => $label,
                'total' => 0,
                'confirmed' => 0,
                'needs_attention' => 0,
                'response_minutes' => [],
            ],
        ]);

        foreach ($bookings as $booking) {
            $bucketDate = Carbon::parse($booking->tour_start_at);
            $label = $isMonthly ? $bucketDate->format('M Y') : 'W'.$bucketDate->format('W');
            if (! $series->has($label)) {
                continue;
            }

            $item = $series->get($label);
            $item['total']++;
            if ($booking->status === 'confirmed') {
                $item['confirmed']++;
            }
            if ($booking->needs_attention) {
                $item['needs_attention']++;
            }

            $firstCustomer = $booking->chatMessages->firstWhere('sender', 'customer');
            $firstOperator = $booking->chatMessages->firstWhere('sender', 'operator');
            if ($firstCustomer && $firstOperator && $firstOperator->created_at->greaterThan($firstCustomer->created_at)) {
                $item['response_minutes'][] = $firstCustomer->created_at->diffInMinutes($firstOperator->created_at);
            }

            $series->put($label, $item);
        }

        $trend = $series->values()->map(function (array $item): array {
            $responseAvg = count($item['response_minutes']) > 0
                ? round(array_sum($item['response_minutes']) / count($item['response_minutes']), 2)
                : 0;

            return [
                'label' => $item['label'],
                'confirmed_rate' => $item['total'] > 0 ? round(($item['confirmed'] / $item['total']) * 100, 2) : 0,
                'attention_rate' => $item['total'] > 0 ? round(($item['needs_attention'] / $item['total']) * 100, 2) : 0,
                'avg_response_minutes' => $responseAvg,
                'volume' => $item['total'],
            ];
        })->all();

        $standbyNow = (clone $viewer->bookingsVisibleQuery())->where('status', 'standby')->count();

        $standbyToConfirmedQuery = DB::table('booking_status_events')
            ->join('bookings', 'bookings.id', '=', 'booking_status_events.booking_id')
            ->where('booking_status_events.old_status', 'standby')
            ->where('booking_status_events.new_status', 'confirmed');

        if (! $viewer->isSuperAdmin()) {
            $standbyToConfirmedQuery->where(function ($tenantScope) use ($viewer): void {
                $tenantScope->where('bookings.tenant_id', $viewer->tenant_id)->orWhereNull('bookings.tenant_id');
            });
        }

        if ($viewer->isGuide()) {
            $standbyToConfirmedQuery->where(function ($q) use ($viewer): void {
                $q->where('bookings.user_id', $viewer->id)->orWhereNull('bookings.user_id');
            });
        }

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

    private function topTags(User $viewer): array
    {
        $tags = $viewer->bookingsVisibleQuery()
            ->whereNotNull('tags')
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
