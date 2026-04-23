<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalendarSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_payload_contains_manual_and_ota_source_labels(): void
    {
        $tenant = Tenant::factory()->create();
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Calendar Source Tour',
            'is_active' => true,
        ]);
        $viewer = User::factory()->create([
            'role' => 'operator',
            'tenant_id' => $tenant->id,
        ]);

        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'tour_start_at' => now()->addDay(),
            'status' => 'confirmed',
            'booking_source' => 'manual',
            'channel' => 'MANUAL',
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'tour_start_at' => now()->addDays(2),
            'status' => 'pending',
            'booking_source' => 'ota',
            'channel' => 'getyourguide',
        ]);

        $response = $this->actingAs($viewer)->get(url('/apps-bookings-calendar'));
        $response->assertOk();

        $events = collect($response->viewData('bookingCalendarEvents'));
        $sources = $events->map(fn (array $event): string => (string) data_get($event, 'extendedProps.bookingSource'))
            ->values()
            ->all();

        $this->assertContains('manual', $sources);
        $this->assertContains('ota', $sources);
    }
}
