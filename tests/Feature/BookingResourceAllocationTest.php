<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingResourceAllocation;
use App\Models\Tenant;
use App\Models\TenantResource;
use App\Models\Tour;
use App\Models\User;
use App\Services\BookingResourceAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingResourceAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_and_unassign_resource_for_booking(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
        ]);
        $resource = TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Boat A',
            'reference_code' => 'BOAT-A',
            'capacity' => 10,
            'status' => 'available',
        ]);

        $this->actingAs($admin)->post(route('bookings.resource-allocations.store', $booking), [
            'tenant_resource_id' => $resource->id,
            'allocation_date' => '2026-08-01',
            'allocated_pax' => 4,
            'notes' => 'Assigned for morning slot',
        ])->assertRedirect(route('index', ['any' => 'apps-bookings']));

        $this->assertDatabaseHas('tenant_audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $admin->id,
            'event_type' => 'resource.allocation.upserted',
        ]);

        $allocation = BookingResourceAllocation::query()
            ->where('booking_id', $booking->id)
            ->where('tenant_resource_id', $resource->id)
            ->firstOrFail();

        $this->actingAs($admin)->post(route('bookings.resource-allocations.destroy', [
            'booking' => $booking,
            'allocation' => $allocation,
        ]))->assertRedirect(route('index', ['any' => 'apps-bookings']));

        $this->assertDatabaseMissing('booking_resource_allocations', [
            'id' => $allocation->id,
        ]);
        $this->assertDatabaseHas('tenant_audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $admin->id,
            'event_type' => 'resource.allocation.deleted',
        ]);
    }

    public function test_assign_resource_rejects_conflict_for_same_date(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $bookingA = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
        ]);
        $bookingB = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
        ]);
        $resource = TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Boat B',
            'reference_code' => 'BOAT-B',
            'capacity' => 8,
            'status' => 'available',
        ]);

        $this->actingAs($admin)->post(route('bookings.resource-allocations.store', $bookingA), [
            'tenant_resource_id' => $resource->id,
            'allocation_date' => '2026-08-02',
            'allocated_pax' => 3,
        ])->assertRedirect(route('index', ['any' => 'apps-bookings']));

        $response = $this->actingAs($admin)
            ->from(url('/apps-bookings'))
            ->post(route('bookings.resource-allocations.store', $bookingB), [
                'tenant_resource_id' => $resource->id,
                'allocation_date' => '2026-08-02',
                'allocated_pax' => 2,
            ]);

        $response->assertRedirect(url('/apps-bookings'));
        $response->assertSessionHasErrors('tenant_resource_id');
    }

    public function test_assign_rejects_non_vehicle_resource_for_snorkeling_tour_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'allocation_requirement' => Tour::ALLOCATION_SNORKELING,
        ]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
        ]);
        $guideResource = TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'guide_driver',
            'name' => 'Guide A',
            'reference_code' => 'GD-A',
            'capacity' => null,
            'status' => 'available',
        ]);

        $this->expectException(ValidationException::class);

        app(BookingResourceAllocationService::class)->assign($booking, [
            'tenant_resource_id' => $guideResource->id,
            'allocation_date' => '2026-08-20',
            'allocated_pax' => 2,
        ], (int) $admin->id);
    }
}
