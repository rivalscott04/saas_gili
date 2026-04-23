<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\TenantResource;
use App\Models\Tour;
use App\Models\User;
use App\Services\BookingResourceAllocationService;

class BookingApiTest extends AuthenticatedApiTestCase
{
    public function test_operator_cannot_access_booking_owned_by_peer(): void
    {
        $peer = User::factory()->create(['role' => 'operator']);
        $booking = Booking::factory()->create(['user_id' => $peer->id]);

        $this->getJson("/api/v1/bookings/{$booking->id}")->assertForbidden();
        $this->patchJson("/api/v1/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ])->assertForbidden();
    }

    public function test_it_lists_bookings_with_filters(): void
    {
        Booking::factory()->create(['customer_id' => null, 'customer_name' => 'Alice', 'status' => 'confirmed']);
        Booking::factory()->create(['customer_id' => null, 'customer_name' => 'Bob', 'status' => 'pending']);

        $response = $this->getJson('/api/v1/bookings?status=confirmed');

        $response->assertOk()
            ->assertJsonPath('data.0.customer_name', 'Alice')
            ->assertJsonCount(1, 'data');
    }

    public function test_it_updates_booking_status(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/v1/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_it_rejects_confirm_without_vehicle_allocation_for_snorkeling_tour(): void
    {
        $tenant = Tenant::factory()->create();
        $this->apiUser->update(['tenant_id' => $tenant->id]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'allocation_requirement' => Tour::ALLOCATION_SNORKELING,
        ]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'status' => 'pending',
            'tour_start_at' => '2026-09-10 08:00:00',
        ]);

        $this->patchJson("/api/v1/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_it_allows_confirm_for_snorkeling_tour_when_vehicle_is_allocated(): void
    {
        $tenant = Tenant::factory()->create();
        $this->apiUser->update(['tenant_id' => $tenant->id]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'allocation_requirement' => Tour::ALLOCATION_SNORKELING,
        ]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'status' => 'pending',
            'tour_start_at' => '2026-09-11 08:00:00',
        ]);
        $vehicle = TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Boat X',
            'reference_code' => 'BX',
            'capacity' => 12,
            'status' => 'available',
        ]);
        app(BookingResourceAllocationService::class)->assign($booking, [
            'tenant_resource_id' => $vehicle->id,
            'allocation_date' => '2026-09-11',
            'allocated_pax' => 4,
        ], (int) $this->apiUser->id);

        $this->patchJson("/api/v1/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ])->assertOk()->assertJsonPath('data.status', 'confirmed');
    }

    public function test_it_issues_magic_link_url_pointing_at_frontend_spa(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'pending',
            'confirmation_token' => null,
        ]);

        $issue = $this->postJson("/api/v1/bookings/{$booking->id}/issue-confirm-link");
        $issue->assertOk()->assertJsonPath('data.booking_id', $booking->id);

        $link = $issue->json('data.confirm_url');
        $this->assertStringContainsString('/booking/'.$booking->id.'/respond', $link);
        $this->assertStringContainsString('token=', $link);
    }

    public function test_magic_link_api_preflight_and_confirm(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'pending',
            'confirmation_token' => null,
            'confirmation_token_hash' => null,
            'confirmation_token_expires_at' => null,
        ]);

        $issue = $this->postJson("/api/v1/bookings/{$booking->id}/issue-confirm-link")->assertOk();
        $link = $issue->json('data.confirm_url');
        $this->assertIsString($link);
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $token = $query['token'] ?? '';
        $this->assertNotSame('', $token);

        $this->getJson("/api/v1/bookings/{$booking->id}/magic-link?token={$token}")
            ->assertOk()
            ->assertJsonPath('data.view', 'form');

        $this->postJson("/api/v1/bookings/{$booking->id}/magic-link", [
            'token' => $token,
            'action' => 'confirm',
        ])->assertOk()
            ->assertJsonPath('data.view', 'done')
            ->assertJsonPath('data.customer_response', 'confirmed');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'customer_response' => 'confirmed',
        ]);
    }

    public function test_magic_link_records_cancel_and_reschedule(): void
    {
        $pending = Booking::factory()->create([
            'status' => 'pending',
            'confirmation_token' => null,
            'confirmation_token_hash' => hash('sha256', 'tok-cancel-test'),
            'confirmation_token_expires_at' => now()->addDay(),
            'needs_attention' => false,
        ]);

        $this->postJson("/api/v1/bookings/{$pending->id}/magic-link", [
            'token' => 'tok-cancel-test',
            'action' => 'cancel',
        ])->assertOk();

        $pending->refresh();
        $this->assertSame('cancelled', $pending->status);
        $this->assertSame('cancelled', $pending->customer_response);

        $confirmed = Booking::factory()->create([
            'status' => 'confirmed',
            'confirmation_token' => null,
            'confirmation_token_hash' => hash('sha256', 'tok-resched-test'),
            'confirmation_token_expires_at' => now()->addDay(),
            'needs_attention' => false,
        ]);

        $this->postJson("/api/v1/bookings/{$confirmed->id}/magic-link", [
            'token' => 'tok-resched-test',
            'action' => 'reschedule',
        ])->assertOk();

        $confirmed->refresh();
        $this->assertSame('pending', $confirmed->status);
        $this->assertTrue($confirmed->needs_attention);
        $this->assertSame('reschedule_requested', $confirmed->customer_response);
    }

    public function test_it_updates_local_guide_fields_without_touching_external_data(): void
    {
        $booking = Booking::factory()->create([
            'tour_name' => 'External Tour Name',
            'status' => 'pending',
            'internal_notes' => null,
            'assigned_to_name' => null,
            'tags' => [],
            'needs_attention' => false,
        ]);

        $response = $this->patchJson("/api/v1/bookings/{$booking->id}/local-fields", [
            'internal_notes' => 'Customer requested hotel pickup.',
            'assigned_to_name' => 'Guide Team A',
            'tags' => ['pickup', 'vip'],
            'needs_attention' => true,
            'channel' => 'getyourguide',
            'channel_order_id' => 'GYG-ORD-1001',
            'currency' => 'eur',
            'gross_amount' => 100,
            'commission_amount' => 20,
            'net_amount' => 80,
            'fx_rate_to_idr' => 17500,
            'pricing_payload_json' => ['tier' => 'adult', 'source' => 'api'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.internal_notes', 'Customer requested hotel pickup.')
            ->assertJsonPath('data.assigned_to_name', 'Guide Team A')
            ->assertJsonPath('data.needs_attention', true)
            ->assertJsonPath('data.tour_name', 'External Tour Name');

        $this->assertArrayNotHasKey('channel', $response->json('data'));
        $this->assertArrayNotHasKey('net_amount', $response->json('data'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'tour_name' => 'External Tour Name',
            'assigned_to_name' => 'Guide Team A',
            'needs_attention' => 1,
            'channel' => null,
        ]);
    }

    public function test_tenant_admin_can_update_and_view_financial_fields(): void
    {
        $tenantAdmin = User::factory()->create(['role' => 'tenant_admin']);
        $this->apiUser = $tenantAdmin;
        \Laravel\Sanctum\Sanctum::actingAs($tenantAdmin);

        $booking = Booking::factory()->create([
            'tenant_id' => $tenantAdmin->tenant_id,
            'user_id' => $tenantAdmin->id,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/v1/bookings/{$booking->id}/local-fields", [
            'channel' => 'getyourguide',
            'channel_order_id' => 'GYG-ORD-1001',
            'currency' => 'eur',
            'gross_amount' => 100,
            'commission_amount' => 20,
            'net_amount' => 80,
            'fx_rate_to_idr' => 17500,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.channel', 'getyourguide')
            ->assertJsonPath('data.channel_order_id', 'GYG-ORD-1001')
            ->assertJsonPath('data.currency', 'EUR')
            ->assertJsonPath('data.net_amount', 80)
            ->assertJsonPath('data.revenue_amount', 1400000)
            ->assertJsonPath('data.booking_source', 'ota');

        $this->assertDatabaseHas('tenant_audit_logs', [
            'tenant_id' => $tenantAdmin->tenant_id,
            'actor_user_id' => $tenantAdmin->id,
            'event_type' => 'booking.source_or_channel.updated',
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
        ]);
    }

    public function test_it_lists_assignee_suggestions_for_visible_bookings(): void
    {
        Booking::factory()->create([
            'user_id' => $this->apiUser->id,
            'assigned_to_name' => 'Guide Team Zebra',
        ]);
        Booking::factory()->create([
            'user_id' => $this->apiUser->id,
            'assigned_to_name' => 'Guide Team Alpha',
        ]);
        Booking::factory()->create([
            'user_id' => $this->apiUser->id,
            'assigned_to_name' => 'Guide Team Alpha',
        ]);

        $peer = User::factory()->create(['role' => 'operator']);
        Booking::factory()->create([
            'user_id' => $peer->id,
            'assigned_to_name' => 'Other Company Guide',
        ]);

        $all = $this->getJson('/api/v1/bookings/assignees')->assertOk()->json('data');
        $this->assertSame(['Guide Team Alpha', 'Guide Team Zebra'], $all);
        $this->assertNotContains('Other Company Guide', $all);

        $filtered = $this->getJson('/api/v1/bookings/assignees?q=Zeb')->assertOk()->json('data');
        $this->assertSame(['Guide Team Zebra'], $filtered);
    }
}
