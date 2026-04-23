<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingResourceAllocation;
use App\Models\TenantResource;
use App\Models\Tour;
use App\Models\TourResourceRequirement;
use Illuminate\Validation\ValidationException;

class TourAllocationRuleService
{
    public function assertResourceAllowedForBooking(Booking $booking, TenantResource $resource): void
    {
        $tour = $this->resolveTour($booking);
        if (! $tour) {
            return;
        }
        $requirements = $this->resolvedRequirements($tour);
        $requiredTypes = collect($requirements)
            ->filter(fn (array $row): bool => ($row['is_required'] ?? false) === true)
            ->keys()
            ->values()
            ->all();
        if ($requiredTypes === []) {
            return;
        }
        $type = strtolower((string) $resource->resource_type);
        if (! in_array($type, $requiredTypes, true)) {
            $allowedLabels = collect($requiredTypes)
                ->map(fn (string $resourceType): string => Tour::RESOURCE_TYPE_LABELS[$resourceType] ?? $resourceType)
                ->implode(', ');
            throw ValidationException::withMessages([
                'tenant_resource_id' => 'Tour ini hanya menerima alokasi resource tipe: '.$allowedLabels.'.',
            ]);
        }
    }

    public function assertReadyForConfirmedStatus(Booking $booking): void
    {
        if ($booking->status === 'cancelled') {
            return;
        }
        $msg = $this->confirmationBlockMessage($booking);
        if ($msg !== null) {
            throw ValidationException::withMessages(['status' => $msg]);
        }
    }

    /**
     * Peringatan ringan di daftar booking bila aturan alokasi belum terpenuhi.
     */
    public function allocationReadinessMessage(Booking $booking): ?string
    {
        if (! in_array((string) $booking->status, ['pending', 'standby', 'confirmed'], true)) {
            return null;
        }

        return $this->confirmationBlockMessage($booking);
    }

    private function confirmationBlockMessage(Booking $booking): ?string
    {
        if (! $booking->tour_id || ! $booking->tour_start_at) {
            return null;
        }
        $tour = $this->resolveTour($booking);
        if (! $tour) {
            return null;
        }
        $requirements = $this->resolvedRequirements($tour);
        $requiredRows = collect($requirements)
            ->filter(fn (array $row): bool => ($row['is_required'] ?? false) === true);
        if ($requiredRows->isEmpty()) {
            return null;
        }
        $serviceDate = $booking->tour_start_at->toDateString();
        $allocations = BookingResourceAllocation::query()
            ->where('booking_id', $booking->id)
            ->whereDate('allocation_date', $serviceDate)
            ->with('resource:id,resource_type')
            ->get();
        $countsByType = $allocations
            ->map(fn (BookingResourceAllocation $row): ?string => $row->resource ? strtolower((string) $row->resource->resource_type) : null)
            ->filter()
            ->countBy();

        $missingMessages = [];
        foreach ($requiredRows as $resourceType => $rule) {
            $requiredUnits = max(1, (int) ($rule['min_units'] ?? 1));
            $actualUnits = (int) ($countsByType[$resourceType] ?? 0);
            if ($actualUnits < $requiredUnits) {
                $label = Tour::RESOURCE_TYPE_LABELS[$resourceType] ?? $resourceType;
                $missingMessages[] = sprintf('%s (min %d, ada %d)', $label, $requiredUnits, $actualUnits);
            }
        }
        if ($missingMessages !== []) {
            return 'Resource belum lengkap: '.implode('; ', $missingMessages).'. Lengkapi sebelum menetapkan status Confirmed.';
        }

        return null;
    }

    private function resolveTour(Booking $booking): ?Tour
    {
        if ($booking->relationLoaded('tour') && $booking->tour) {
            return $booking->tour;
        }

        return Tour::query()->find((int) $booking->tour_id);
    }

    /**
     * @return array<string, array{is_required: bool, min_units: int}>
     */
    private function resolvedRequirements(Tour $tour): array
    {
        $default = [
            'vehicle' => ['is_required' => false, 'min_units' => 1],
            'guide_driver' => ['is_required' => false, 'min_units' => 1],
            'equipment' => ['is_required' => false, 'min_units' => 1],
        ];
        $rows = $tour->relationLoaded('resourceRequirements')
            ? $tour->resourceRequirements
            : TourResourceRequirement::query()->where('tour_id', $tour->id)->get();

        if ($rows->isEmpty()) {
            $profile = (string) ($tour->allocation_requirement ?? Tour::ALLOCATION_NONE);
            if ($profile === Tour::ALLOCATION_SNORKELING) {
                $default['vehicle']['is_required'] = true;
            } elseif ($profile === Tour::ALLOCATION_LAND_ACTIVITY) {
                $default['vehicle']['is_required'] = true;
                $default['guide_driver']['is_required'] = true;
            }

            return $default;
        }

        foreach ($rows as $row) {
            $resourceType = strtolower((string) $row->resource_type);
            if (! array_key_exists($resourceType, $default)) {
                continue;
            }
            $default[$resourceType] = [
                'is_required' => (bool) $row->is_required,
                'min_units' => max(1, (int) $row->min_units),
            ];
        }

        return $default;
    }
}
