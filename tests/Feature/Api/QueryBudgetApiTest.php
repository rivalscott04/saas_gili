<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\Customer;
use Tests\Concerns\TracksQueryCount;

class QueryBudgetApiTest extends AuthenticatedApiTestCase
{
    use TracksQueryCount;

    public function test_bookings_index_query_growth_is_bounded(): void
    {
        $this->seedBookings(4);
        $smallCount = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/bookings?per_page=100')->assertOk();
        });

        $this->seedBookings(30);
        $largeCount = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/bookings?per_page=100')->assertOk();
        });

        $this->assertLessThanOrEqual(
            $smallCount + 3,
            $largeCount,
            "Expected bounded query growth for bookings index, got small={$smallCount}, large={$largeCount}"
        );
    }

    public function test_dashboard_lists_query_growth_is_bounded(): void
    {
        $this->seedBookings(5);
        $smallUrgent = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/dashboard/urgent-bookings?limit=50')->assertOk();
        });
        $smallRecent = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/dashboard/recent-bookings?limit=50')->assertOk();
        });

        $this->seedBookings(25);
        $largeUrgent = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/dashboard/urgent-bookings?limit=50')->assertOk();
        });
        $largeRecent = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/dashboard/recent-bookings?limit=50')->assertOk();
        });

        $this->assertLessThanOrEqual(
            $smallUrgent + 3,
            $largeUrgent,
            "Expected bounded query growth for urgent bookings, got small={$smallUrgent}, large={$largeUrgent}"
        );
        $this->assertLessThanOrEqual(
            $smallRecent + 3,
            $largeRecent,
            "Expected bounded query growth for recent bookings, got small={$smallRecent}, large={$largeRecent}"
        );
    }

    public function test_analytics_overview_and_trends_query_growth_is_bounded(): void
    {
        $this->seedBookings(6, withChats: true);
        $smallOverview = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/analytics/overview')->assertOk();
        });
        $smallTrends = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/analytics/trends?period=weekly')->assertOk();
        });

        $this->seedBookings(30, withChats: true);
        $largeOverview = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/analytics/overview')->assertOk();
        });
        $largeTrends = $this->countSelectQueries(function (): void {
            $this->getJson('/api/v1/analytics/trends?period=weekly')->assertOk();
        });

        $this->assertLessThanOrEqual(
            $smallOverview + 4,
            $largeOverview,
            "Expected bounded query growth for analytics overview, got small={$smallOverview}, large={$largeOverview}"
        );
        $this->assertLessThanOrEqual(
            $smallTrends + 4,
            $largeTrends,
            "Expected bounded query growth for analytics trends, got small={$smallTrends}, large={$largeTrends}"
        );
    }

    private function seedBookings(int $count, bool $withChats = false): void
    {
        for ($i = 0; $i < $count; $i++) {
            $customer = Customer::factory()->create([
                'tenant_id' => $this->apiUser->tenant_id,
            ]);

            $booking = Booking::factory()->create([
                'user_id' => $this->apiUser->id,
                'tenant_id' => $this->apiUser->tenant_id,
                'customer_id' => $customer->id,
                'tour_start_at' => now()->addDays(($i % 7) + 1),
                'status' => $i % 2 === 0 ? 'confirmed' : 'pending',
                'needs_attention' => $i % 3 === 0,
            ]);

            if ($withChats) {
                ChatMessage::factory()->create([
                    'booking_id' => $booking->id,
                    'sender' => 'customer',
                    'created_at' => now()->subMinutes(10),
                ]);
                ChatMessage::factory()->create([
                    'booking_id' => $booking->id,
                    'sender' => 'operator',
                    'created_at' => now()->subMinutes(5),
                ]);
            }
        }
    }
}
