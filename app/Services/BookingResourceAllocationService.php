<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingResourceAllocation;
use App\Models\TenantResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingResourceAllocationService
{
    public function __construct(
        private readonly TenantAuditLogService $tenantAuditLogService,
        private readonly TourAllocationRuleService $tourAllocationRuleService
    ) {
    }

    public function assign(Booking $booking, array $payload, ?int $actorUserId = null): BookingResourceAllocation
    {
        return DB::transaction(function () use ($booking, $payload, $actorUserId) {
            $resource = TenantResource::query()
                ->whereKey((int) $payload['tenant_resource_id'])
                ->where('tenant_id', $booking->tenant_id)
                ->lockForUpdate()
                ->first();
            if (! $resource) {
                throw ValidationException::withMessages([
                    'tenant_resource_id' => 'Resource tidak valid untuk tenant booking ini.',
                ]);
            }
            if (strtolower((string) $resource->status) !== 'available') {
                throw ValidationException::withMessages([
                    'tenant_resource_id' => 'Resource sedang tidak tersedia.',
                ]);
            }

            $this->tourAllocationRuleService->assertResourceAllowedForBooking($booking, $resource);

            $allocationDate = (string) $payload['allocation_date'];
            $conflict = BookingResourceAllocation::query()
                ->where('tenant_resource_id', $resource->id)
                ->whereDate('allocation_date', $allocationDate)
                ->where('booking_id', '!=', $booking->id)
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages([
                    'tenant_resource_id' => 'Resource sudah dialokasikan ke booking lain pada tanggal ini.',
                ]);
            }

            $allocatedPax = isset($payload['allocated_pax']) && $payload['allocated_pax'] !== null
                ? (int) $payload['allocated_pax']
                : null;
            if ($allocatedPax !== null && $resource->capacity !== null && $allocatedPax > (int) $resource->capacity) {
                throw ValidationException::withMessages([
                    'allocated_pax' => "Alokasi pax melebihi kapasitas resource ({$resource->capacity}).",
                ]);
            }

            $allocation = BookingResourceAllocation::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'tenant_resource_id' => $resource->id,
                    'allocation_date' => $allocationDate,
                ],
                [
                    'tenant_id' => $booking->tenant_id,
                    'allocated_units' => isset($payload['allocated_units']) ? (int) $payload['allocated_units'] : null,
                    'allocated_pax' => $allocatedPax,
                    'notes' => $payload['notes'] ?? null,
                ]
            );

            $this->tenantAuditLogService->record(
                tenantId: (int) $booking->tenant_id,
                actorUserId: $actorUserId,
                eventType: 'resource.allocation.upserted',
                entityType: 'booking_resource_allocation',
                entityId: (int) $allocation->id,
                tourId: $booking->tour_id ? (int) $booking->tour_id : null,
                serviceDate: $allocation->allocation_date,
                context: [
                    'booking_id' => $booking->id,
                    'resource_id' => $resource->id,
                    'resource_name' => $resource->name,
                    'allocated_units' => $allocation->allocated_units,
                    'allocated_pax' => $allocation->allocated_pax,
                ]
            );

            return $allocation;
        });
    }

    public function unassign(Booking $booking, BookingResourceAllocation $allocation, ?int $actorUserId = null): void
    {
        if ((int) $allocation->booking_id !== (int) $booking->id || (int) $allocation->tenant_id !== (int) $booking->tenant_id) {
            throw ValidationException::withMessages([
                'allocation' => 'Alokasi tidak valid untuk booking ini.',
            ]);
        }

        $this->tenantAuditLogService->record(
            tenantId: (int) $booking->tenant_id,
            actorUserId: $actorUserId,
            eventType: 'resource.allocation.deleted',
            entityType: 'booking_resource_allocation',
            entityId: (int) $allocation->id,
            tourId: $booking->tour_id ? (int) $booking->tour_id : null,
            serviceDate: $allocation->allocation_date,
            context: [
                'booking_id' => $booking->id,
                'resource_id' => $allocation->tenant_resource_id,
            ]
        );

        $allocation->delete();
    }
}
