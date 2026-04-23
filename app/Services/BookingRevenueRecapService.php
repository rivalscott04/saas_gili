<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingRevenueRecapService
{
    /**
     * @param array{specific_date?: string|null, date_from?: string|null, date_to?: string|null, channel?: string|null} $filters
     * @return array{summary: array<string, int|float>, per_channel: Collection<int, object>, trend_daily: Collection<int, object>, channels: Collection<int, string>}
     */
    public function recap(User $viewer, array $filters): array
    {
        $baseQuery = $viewer->bookingsVisibleQuery();
        $this->applyFilters($baseQuery, $filters);

        $revenueExpr = $this->revenueIdrExpression();
        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_bookings')
            ->selectRaw('COALESCE(SUM(participants), 0) as total_pax')
            ->selectRaw('COALESCE(SUM(gross_amount * COALESCE(NULLIF(fx_rate_to_idr, 0), 1)), 0) as gross_idr')
            ->selectRaw('COALESCE(SUM('.$revenueExpr.'), 0) as net_idr')
            ->first();

        $perChannel = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(channel, ''), 'unknown') as channel_label")
            ->selectRaw('COUNT(*) as total_bookings')
            ->selectRaw('COALESCE(SUM(participants), 0) as total_pax')
            ->selectRaw('COALESCE(SUM('.$revenueExpr.'), 0) as net_idr')
            ->groupBy('channel_label')
            ->orderByDesc('net_idr')
            ->get();

        $dailyTrend = (clone $baseQuery)
            ->selectRaw('DATE(tour_start_at) as trend_date')
            ->selectRaw('COALESCE(SUM('.$revenueExpr.'), 0) as net_idr')
            ->whereNotNull('tour_start_at')
            ->groupBy('trend_date')
            ->orderBy('trend_date')
            ->get();

        $channels = Booking::query()
            ->visibleToUser($viewer)
            ->whereNotNull('channel')
            ->where('channel', '!=', '')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel')
            ->values();

        return [
            'summary' => [
                'total_bookings' => (int) ($summaryRow->total_bookings ?? 0),
                'total_pax' => (int) ($summaryRow->total_pax ?? 0),
                'gross_idr' => (float) ($summaryRow->gross_idr ?? 0),
                'net_idr' => (float) ($summaryRow->net_idr ?? 0),
            ],
            'per_channel' => $perChannel,
            'trend_daily' => $dailyTrend,
            'channels' => $channels,
        ];
    }

    /**
     * @param array{specific_date?: string|null, date_from?: string|null, date_to?: string|null, channel?: string|null} $filters
     */
    public function export(User $viewer, array $filters, string $format = 'csv', string $delimiterMode = 'semicolon'): StreamedResponse
    {
        $baseQuery = $viewer->bookingsVisibleQuery();
        $this->applyFilters($baseQuery, $filters);

        $rows = (clone $baseQuery)
            ->orderBy('tour_start_at')
            ->get([
                'id',
                'tour_start_at',
                'channel',
                'currency',
                'net_amount',
                'fx_rate_to_idr',
                'revenue_amount',
                'participants',
                'status',
            ]);

        $delimiter = $delimiterMode === 'colon' ? ':' : ';';
        $isExcel = $format === 'excel';
        $filename = $isExcel ? 'booking-revenue-recap.xls' : 'booking-revenue-recap.csv';
        $contentType = $isExcel ? 'application/vnd.ms-excel; charset=UTF-8' : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($rows, $delimiter): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'No. Booking',
                'Tanggal Keberangkatan',
                'Sumber Pesanan',
                'Mata Uang',
                'Pendapatan Bersih (Mata Uang Asal)',
                'Nilai Tukar ke Rupiah',
                'Pendapatan Bersih (Rupiah)',
                'Jumlah Peserta',
                'Status Pesanan',
            ], $delimiter);

            foreach ($rows as $row) {
                $currency = strtoupper((string) ($row->currency ?? 'IDR'));
                $netOriginal = (float) ($row->net_amount ?? 0);
                $fxRate = (float) ($row->fx_rate_to_idr ?? 0);
                $netIdr = (float) ($row->revenue_amount ?? 0);
                if ($netIdr <= 0) {
                    $netIdr = $currency === 'IDR' ? $netOriginal : ($fxRate > 0 ? $netOriginal * $fxRate : 0);
                }

                fputcsv($out, [
                    $row->id,
                    optional($row->tour_start_at)?->format('d/m/Y H:i'),
                    $this->friendlyChannelLabel((string) ($row->channel ?? '')),
                    $currency,
                    number_format($netOriginal, 2, '.', ''),
                    number_format($fxRate, 6, '.', ''),
                    number_format($netIdr, 2, '.', ''),
                    $row->participants,
                    $this->friendlyStatusLabel((string) ($row->status ?? '')),
                ], $delimiter);
            }
            fclose($out);
        }, $filename, ['Content-Type' => $contentType]);
    }

    /**
     * @param array{specific_date?: string|null, date_from?: string|null, date_to?: string|null, channel?: string|null} $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $specificDate = trim((string) ($filters['specific_date'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $channel = trim((string) ($filters['channel'] ?? ''));

        if ($specificDate !== '') {
            $query->whereDate('tour_start_at', $specificDate);
        } else {
            if ($dateFrom !== '') {
                $query->whereDate('tour_start_at', '>=', $dateFrom);
            }
            if ($dateTo !== '') {
                $query->whereDate('tour_start_at', '<=', $dateTo);
            }
        }

        if ($channel !== '') {
            $query->where('channel', $channel);
        }
    }

    private function revenueIdrExpression(): string
    {
        return "COALESCE(revenue_amount, CASE WHEN UPPER(COALESCE(currency, 'IDR')) = 'IDR' THEN COALESCE(net_amount, 0) ELSE COALESCE(net_amount, 0) * COALESCE(NULLIF(fx_rate_to_idr, 0), 0) END, 0)";
    }

    private function friendlyChannelLabel(string $channel): string
    {
        $normalized = strtolower(trim($channel));

        return match ($normalized) {
            'getyourguide' => 'GetYourGuide',
            'viator' => 'Viator',
            'klook' => 'Klook',
            'direct' => 'Penjualan Langsung',
            'manual' => 'Input Manual',
            '' => 'Tidak Diketahui',
            default => strtoupper($channel),
        };
    }

    private function friendlyStatusLabel(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'standby' => 'Menunggu',
            'pending' => 'Perlu Konfirmasi',
            'confirmed' => 'Terkonfirmasi',
            'cancelled' => 'Dibatalkan',
            'completed' => 'Selesai',
            '' => 'Tidak Diketahui',
            default => ucfirst($status),
        };
    }
}
