<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ChatMessage;
use App\Models\Customer;

class AnalyticsApiTest extends AuthenticatedApiTestCase
{
    public function test_it_returns_analytics_overview(): void
    {
        $customerA = Customer::factory()->create(['external_source' => 'getyourguide']);
        $customerB = Customer::factory()->create(['external_source' => 'manual']);

        Booking::factory()->create([
            'customer_id' => $customerA->id,
            'status' => 'confirmed',
            'needs_attention' => false,
            'tags' => ['pickup', 'vip'],
        ]);
        Booking::factory()->create([
            'customer_id' => $customerA->id,
            'status' => 'pending',
            'needs_attention' => true,
            'tags' => ['pickup'],
        ]);
        Booking::factory()->create([
            'customer_id' => $customerB->id,
            'status' => 'cancelled',
            'needs_attention' => false,
            'tags' => ['reschedule'],
        ]);

        $response = $this->getJson('/api/v1/analytics/overview');

        $response->assertOk()
            ->assertJsonPath('data.total_customers', 2)
            ->assertJsonPath('data.total_bookings', 3)
            ->assertJsonPath('data.confirmed_rate', 33.33)
            ->assertJsonPath('data.repeat_customer_rate', 50);
    }

    public function test_it_returns_analytics_trends_and_funnel(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'standby',
            'needs_attention' => true,
            'tour_start_at' => now()->addDay(),
        ]);
        ChatMessage::factory()->create([
            'booking_id' => $booking->id,
            'sender' => 'customer',
            'created_at' => now()->subMinutes(60),
        ]);
        ChatMessage::factory()->create([
            'booking_id' => $booking->id,
            'sender' => 'operator',
            'created_at' => now()->subMinutes(30),
        ]);
        BookingStatusEvent::query()->create([
            'booking_id' => $booking->id,
            'old_status' => 'standby',
            'new_status' => 'confirmed',
            'changed_by' => 'system',
            'reason' => 'wa_positive_reply',
        ]);

        $response = $this->getJson('/api/v1/analytics/trends?period=weekly');

        $response->assertOk()
            ->assertJsonPath('data.period', 'weekly')
            ->assertJsonStructure([
                'data' => [
                    'trend',
                    'funnel' => ['standby_now', 'standby_to_confirmed', 'conversion_rate'],
                ],
            ]);
    }

    public function test_it_exports_bookings_csv(): void
    {
        Booking::factory()->create();

        $response = $this->get('/api/v1/analytics/export/bookings.csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
