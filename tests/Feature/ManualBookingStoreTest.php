<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualBookingStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_create_manual_booking_and_redirects_to_list(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'role' => 'operator',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sunset Cruise',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('bookings.manual.store'), [
            'tour_id' => $tour->id,
            'tour_start_at' => '2026-05-01T08:00',
            'participants' => 4,
            'status' => 'confirmed',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+6281234567890',
        ]);

        $response->assertRedirect(url('/apps-bookings'));

        $this->assertDatabaseHas('bookings', [
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => 'Sunset Cruise',
            'status' => 'confirmed',
            'booking_source' => 'manual',
            'participants' => 4,
            'channel' => 'MANUAL',
        ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_superadmin_must_submit_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Island Hop',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('bookings.manual.store'), [
            'tour_id' => $tour->id,
            'tour_start_at' => '2026-05-02T09:00',
            'participants' => 2,
            'status' => 'pending',
            'customer_name' => 'Alex',
        ])->assertSessionHasErrors('on_behalf_tenant_id');

        $this->actingAs($user)->post(route('bookings.manual.store'), [
            'on_behalf_tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_start_at' => '2026-05-02T09:00',
            'participants' => 2,
            'status' => 'pending',
            'customer_name' => 'Alex',
        ])->assertRedirect(url('/apps-bookings'));

        $this->assertDatabaseHas('bookings', [
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => 'Island Hop',
        ]);
    }

    public function test_manual_booking_is_rejected_when_capacity_is_full(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'role' => 'operator',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Capacity Test Tour',
            'default_max_pax_per_day' => 4,
            'is_active' => true,
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'tour_name' => $tour->name,
            'tour_start_at' => '2026-06-01 08:00:00',
            'participants' => 3,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($user)->from(route('bookings.manual.create'))->post(route('bookings.manual.store'), [
            'tour_id' => $tour->id,
            'tour_start_at' => '2026-06-01T10:00',
            'participants' => 2,
            'status' => 'pending',
            'customer_name' => 'Capacity Guest',
        ]);

        $response->assertRedirect(route('bookings.manual.create'));
        $response->assertSessionHasErrors('participants');
    }

    public function test_manual_booking_uses_day_capacity_override_before_default(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'role' => 'operator',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Override Capacity Tour',
            'default_max_pax_per_day' => 10,
            'is_active' => true,
        ]);
        TourDayCapacity::query()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'service_date' => '2026-06-02',
            'max_pax' => 3,
        ]);

        $this->actingAs($user)->post(route('bookings.manual.store'), [
            'tour_id' => $tour->id,
            'tour_start_at' => '2026-06-02T07:30',
            'participants' => 2,
            'status' => 'confirmed',
            'customer_name' => 'Override Guest A',
        ])->assertRedirect(url('/apps-bookings'));

        $this->actingAs($user)->from(route('bookings.manual.create'))->post(route('bookings.manual.store'), [
            'tour_id' => $tour->id,
            'tour_start_at' => '2026-06-02T09:30',
            'participants' => 2,
            'status' => 'pending',
            'customer_name' => 'Override Guest B',
        ])->assertSessionHasErrors('participants');
    }
}
