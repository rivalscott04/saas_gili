<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourManagementWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_create_update_and_archive_tour(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($admin)->post(route('tours.store'), [
            'name' => 'Snorkeling Premium',
            'default_max_pax_per_day' => 30,
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertRedirect(route('tours.index'));

        $tour = Tour::query()->where('tenant_id', $tenant->id)->where('name', 'Snorkeling Premium')->firstOrFail();
        $this->assertSame('SNORKELING-PREMIUM', $tour->code);

        $this->actingAs($admin)->post(route('tours.update', $tour), [
            'name' => 'Snorkeling Premium Updated',
            'default_max_pax_per_day' => 35,
            'sort_order' => 3,
            'is_active' => '1',
            'allocation_requirement' => 'snorkeling',
        ])->assertRedirect(route('tours.index'));

        $tour->refresh();
        $this->assertSame('Snorkeling Premium Updated', $tour->name);
        $this->assertSame('SNORKELING-PREMIUM', $tour->code);
        $this->assertSame('snorkeling', $tour->allocation_requirement);
        $this->assertDatabaseHas('tour_resource_requirements', [
            'tour_id' => $tour->id,
            'resource_type' => 'vehicle',
            'is_required' => true,
            'min_units' => 1,
        ]);
        $this->assertDatabaseHas('tour_resource_requirements', [
            'tour_id' => $tour->id,
            'resource_type' => 'guide_driver',
            'is_required' => false,
        ]);

        $this->actingAs($admin)->post(route('tours.archive', $tour))
            ->assertRedirect(route('tours.index'));

        $this->assertFalse($tour->fresh()->is_active);
    }

    public function test_superadmin_must_send_tenant_code_to_create_tour(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'tenant-a']);
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);

        $this->actingAs($superadmin)->post(route('tours.store'), [
            'name' => 'Island Explorer',
        ])->assertSessionHasErrors('tenant_code');

        $this->actingAs($superadmin)->post(route('tours.store'), [
            'tenant_code' => $tenant->code,
            'name' => 'Island Explorer',
            'is_active' => '1',
        ])->assertRedirect(route('tours.index', ['tenant' => $tenant->code]));

        $this->assertDatabaseHas('tours', [
            'tenant_id' => $tenant->id,
            'name' => 'Island Explorer',
        ]);
    }

    public function test_tenant_admin_cannot_archive_tour_from_other_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenantA->id,
        ]);
        $tourB = Tour::factory()->create([
            'tenant_id' => $tenantB->id,
            'is_active' => true,
        ]);

        $this->actingAs($adminA)
            ->post(route('tours.archive', $tourB))
            ->assertForbidden();
    }

    public function test_tenant_admin_can_override_resource_requirements_on_tour_update(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'allocation_requirement' => Tour::ALLOCATION_LAND_ACTIVITY,
        ]);

        $this->actingAs($admin)->post(route('tours.update', $tour), [
            'name' => $tour->name,
            'default_max_pax_per_day' => 20,
            'sort_order' => 0,
            'is_active' => '1',
            'allocation_requirement' => 'none',
            'requirements' => [
                'vehicle' => ['is_required' => '0', 'min_units' => 1],
                'guide_driver' => ['is_required' => '0', 'min_units' => 1],
                'equipment' => ['is_required' => '1', 'min_units' => 2],
            ],
        ])->assertRedirect(route('tours.index'));

        $tour->refresh();
        $this->assertSame('none', $tour->allocation_requirement);
        $this->assertDatabaseHas('tour_resource_requirements', [
            'tour_id' => $tour->id,
            'resource_type' => 'equipment',
            'is_required' => true,
            'min_units' => 2,
        ]);
        $this->assertDatabaseMissing('tour_resource_requirements', [
            'tour_id' => $tour->id,
            'resource_type' => 'vehicle',
            'is_required' => true,
        ]);
    }

    public function test_tour_code_cannot_be_submitted_manually(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->post(route('tours.store'), [
            'name' => 'Manual Code Attempt',
            'code' => 'MANUAL-CODE',
        ])->assertSessionHasErrors('code');

        $this->actingAs($admin)->post(route('tours.update', $tour), [
            'name' => $tour->name,
            'code' => 'TRY-OVERRIDE',
        ])->assertSessionHasErrors('code');
    }
}
