<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;

class TourBackfillService
{
    public function backfillFromBookingSnapshots(): int
    {
        $updatedBookings = 0;

        Booking::query()
            ->whereNotNull('tenant_id')
            ->whereNotNull('tour_name')
            ->where('tour_name', '!=', '')
            ->select('tenant_id', 'tour_name')
            ->distinct()
            ->orderBy('tenant_id')
            ->chunk(500, function ($pairs) use (&$updatedBookings): void {
                foreach ($pairs as $pair) {
                    $tenantId = (int) $pair->tenant_id;
                    $tourName = trim((string) $pair->tour_name);
                    if ($tenantId <= 0 || $tourName === '') {
                        continue;
                    }

                    $tour = Tour::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'name' => $tourName,
                        ],
                        [
                            'is_active' => true,
                            'sort_order' => 0,
                        ]
                    );

                    $updatedBookings += Booking::query()
                        ->where('tenant_id', $tenantId)
                        ->where('tour_name', $tourName)
                        ->whereNull('tour_id')
                        ->update(['tour_id' => $tour->id]);
                }
            });

        return $updatedBookings;
    }
}
