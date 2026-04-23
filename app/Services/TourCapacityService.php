<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class TourCapacityService
{
    /**
     * @var array<int, string>
     */
    private const COUNTED_STATUSES = ['pending', 'confirmed', 'standby'];

    public function effectiveMaxPax(Tour $tour, CarbonInterface $serviceDate): ?int
    {
        $dateKey = $serviceDate->toDateString();
        $dailyOverride = TourDayCapacity::query()
            ->where('tour_id', $tour->id)
            ->whereDate('service_date', $dateKey)
            ->value('max_pax');

        if ($dailyOverride !== null) {
            return (int) $dailyOverride;
        }

        return $tour->default_max_pax_per_day !== null
            ? (int) $tour->default_max_pax_per_day
            : null;
    }

    public function assertCanBook(
        Tour $tour,
        CarbonInterface $serviceDate,
        int $requestedPax,
        ?int $ignoreBookingId = null
    ): void {
        $effectiveMaxPax = $this->effectiveMaxPax($tour, $serviceDate);
        if ($effectiveMaxPax === null) {
            return;
        }

        $dateKey = $serviceDate->toDateString();
        $bookedPax = Booking::query()
            ->where('tenant_id', $tour->tenant_id)
            ->where('tour_id', $tour->id)
            ->whereDate('tour_start_at', $dateKey)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->when($ignoreBookingId !== null, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->lockForUpdate()
            ->sum('participants');

        if (((int) $bookedPax + $requestedPax) <= $effectiveMaxPax) {
            return;
        }

        throw ValidationException::withMessages([
            'participants' => "Kapasitas penuh untuk tour ini pada {$dateKey}. Tersedia maksimal {$effectiveMaxPax} pax.",
        ]);
    }
}
