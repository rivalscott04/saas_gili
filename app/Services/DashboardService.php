<?php

namespace App\Services;

use App\Models\Booking;
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
            'commission_total' => round((float) (clone $base)->where('status', 'confirmed')->sum('commission_amount'), 2),
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

    private function scopedBookingsQuery(User $viewer, ?int $tenantId = null)
    {
        $query = $viewer->bookingsVisibleQuery();
        if ($viewer->isSuperAdmin() && $tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }
}
