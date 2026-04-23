<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_bookings_by_status_and_search(): void
    {
        Booking::factory()->create([
            'tour_name' => 'Bali Sunrise Tour',
            'customer_name' => 'Alice',
            'status' => 'confirmed',
        ]);
        Booking::factory()->create([
            'tour_name' => 'Komodo Day Trip',
            'customer_name' => 'Bob',
            'status' => 'pending',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $service = app(BookingService::class);
        $result = $service->paginate([
            'search' => 'Bali',
            'status' => 'confirmed',
            'per_page' => 10,
        ], $admin);

        $this->assertCount(1, $result->items());
        $this->assertSame('Alice', $result->items()[0]->customer_name);
    }

    public function test_it_filters_bookings_by_comma_separated_status(): void
    {
        Booking::factory()->create([
            'customer_name' => 'Standby Guest',
            'status' => 'standby',
        ]);
        Booking::factory()->create([
            'customer_name' => 'Pending Guest',
            'status' => 'pending',
        ]);
        Booking::factory()->create([
            'customer_name' => 'Confirmed Guest',
            'status' => 'confirmed',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $service = app(BookingService::class);
        $result = $service->paginate([
            'status' => 'standby,pending',
            'per_page' => 10,
        ], $admin);

        $names = collect($result->items())->pluck('customer_name')->sort()->values()->all();
        $this->assertSame(['Pending Guest', 'Standby Guest'], $names);
    }

    public function test_it_completes_reschedule_workflow_and_updates_booking_date(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'pending',
            'needs_attention' => true,
            'customer_response' => 'reschedule_requested',
            'tour_start_at' => now()->addDays(2),
        ]);
        $reschedule = BookingReschedule::query()->create([
            'booking_id' => $booking->id,
            'requested_by' => 'customer',
            'request_source' => 'magic_link',
            'workflow_status' => 'requested',
            'old_tour_start_at' => $booking->tour_start_at,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $service = app(BookingService::class);
        $newDate = now()->addDays(5);
        $service->updateRescheduleWorkflow($booking, $reschedule, [
            'workflow_status' => 'completed',
            'final_tour_start_at' => $newDate->toDateTimeString(),
            'notes' => 'Approved and finalized by ops',
        ], (int) $admin->id);

        $booking->refresh();
        $reschedule->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertFalse((bool) $booking->needs_attention);
        $this->assertNull($booking->customer_response);
        $this->assertSame($newDate->format('Y-m-d H:i'), optional($booking->tour_start_at)->format('Y-m-d H:i'));
        $this->assertSame('completed', $reschedule->workflow_status);
        $this->assertNotNull($reschedule->completed_at);
        $this->assertSame($admin->id, $reschedule->reviewed_by_user_id);
    }

    public function test_it_rejects_reschedule_completion_when_capacity_would_overbook(): void
    {
        $tenant = Tenant::factory()->create();
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'default_max_pax_per_day' => 4,
            'is_active' => true,
        ]);

        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'tour_start_at' => '2026-07-01 08:00:00',
            'participants' => 3,
            'status' => 'confirmed',
        ]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'tour_start_at' => '2026-07-02 08:00:00',
            'participants' => 2,
            'status' => 'pending',
            'needs_attention' => true,
            'customer_response' => 'reschedule_requested',
        ]);

        $reschedule = BookingReschedule::query()->create([
            'booking_id' => $booking->id,
            'requested_by' => 'customer',
            'request_source' => 'magic_link',
            'workflow_status' => 'requested',
            'old_tour_start_at' => $booking->tour_start_at,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $service = app(BookingService::class);

        $this->expectException(ValidationException::class);

        $service->updateRescheduleWorkflow($booking, $reschedule, [
            'workflow_status' => 'completed',
            'final_tour_start_at' => '2026-07-01 10:00:00',
            'notes' => 'Would exceed daily capacity',
        ], (int) $admin->id);
    }
}
