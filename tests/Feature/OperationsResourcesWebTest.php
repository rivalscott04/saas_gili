<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantResource;
use App\Models\Tour;
use App\Models\TourResourceRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsResourcesWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_sees_only_own_tenant_resources(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'tenant_admin',
        ]);

        TenantResource::query()->create([
            'tenant_id' => $tenantA->id,
            'resource_type' => 'vehicle',
            'name' => 'Bus Alpha',
            'status' => 'available',
        ]);
        TenantResource::query()->create([
            'tenant_id' => $tenantB->id,
            'resource_type' => 'vehicle',
            'name' => 'Bus Beta',
            'status' => 'available',
        ]);

        $this->actingAs($adminA)
            ->get(route('operations-resources.index'))
            ->assertOk()
            ->assertSee('Bus Alpha')
            ->assertDontSee('Bus Beta');
    }

    public function test_superadmin_can_add_resource_for_selected_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'superadmin',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('operations-resources.store'), [
                'tenant_code' => $tenantB->code,
                'resource_type' => 'equipment',
                'name' => 'Snorkel Set 12',
                'reference_code' => 'EQ-SN-12',
                'capacity' => 12,
            ])
            ->assertRedirect(route('operations-resources.index', ['tenant' => $tenantB->code]));

        $this->assertDatabaseHas('tenant_resources', [
            'tenant_id' => $tenantB->id,
            'name' => 'Snorkel Set 12',
            'resource_type' => 'equipment',
        ]);
        $this->assertDatabaseMissing('tenant_resources', [
            'tenant_id' => $tenantA->id,
            'name' => 'Snorkel Set 12',
        ]);
    }

    public function test_tenant_admin_can_update_and_delete_own_resource(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        $resource = TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Bus Ops 01',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->post(route('operations-resources.update', $resource), [
                'resource_type' => 'vehicle',
                'name' => 'Bus Ops 01 Rev',
                'reference_code' => 'BUS-OPS-01',
                'capacity' => 20,
            ])
            ->assertRedirect(route('operations-resources.index'));

        $this->assertDatabaseHas('tenant_resources', [
            'id' => $resource->id,
            'name' => 'Bus Ops 01 Rev',
            'capacity' => 20,
        ]);

        $this->actingAs($admin)
            ->post(route('operations-resources.destroy', $resource))
            ->assertRedirect(route('operations-resources.index'));

        $this->assertDatabaseMissing('tenant_resources', [
            'id' => $resource->id,
        ]);
    }

    public function test_resource_creation_requires_reference_code_and_capacity_for_vehicle(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);

        $this->actingAs($admin)
            ->from(route('operations-resources.index'))
            ->post(route('operations-resources.store'), [
                'resource_type' => 'vehicle',
                'name' => 'Bus Tanpa Kode',
                'reference_code' => '',
                'capacity' => null,
            ])
            ->assertRedirect(route('operations-resources.index'));

        $this->actingAs($admin)
            ->get(route('operations-resources.index'))
            ->assertSee('Validasi gagal.');

        $this->assertDatabaseMissing('tenant_resources', [
            'tenant_id' => $tenant->id,
            'name' => 'Bus Tanpa Kode',
        ]);
    }

    public function test_legacy_tenant_id_query_parameter_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'superadmin',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('operations-resources.index', ['tenant_id' => $tenant->id]))
            ->assertForbidden();
    }

    public function test_superadmin_cannot_scope_create_payload_using_numeric_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'superadmin',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('operations-resources.store'), [
                'tenant_id' => $tenant->id,
                'resource_type' => 'equipment',
                'name' => 'Should Not Persist',
                'reference_code' => 'EQ-NOPE',
                'capacity' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tenant_resources', [
            'tenant_id' => $tenant->id,
            'name' => 'Should Not Persist',
        ]);
    }

    public function test_resource_page_shows_linked_tour_requirements_by_resource_type(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Boat Ops 1',
            'reference_code' => 'BOAT-1',
            'status' => 'available',
        ]);
        $tour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tour Snorkel Linked',
        ]);
        TourResourceRequirement::query()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'resource_type' => 'vehicle',
            'is_required' => true,
            'min_units' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('operations-resources.index'))
            ->assertOk()
            ->assertSee('Tour Snorkel Linked')
            ->assertSee('min 2');
    }

    public function test_resource_filter_can_show_only_types_used_by_active_tours(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Boat Active Type',
            'reference_code' => 'B-ACT',
            'status' => 'available',
        ]);
        TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'equipment',
            'name' => 'Equipment Non Active',
            'reference_code' => 'EQ-NA',
            'status' => 'available',
        ]);
        $activeTour = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        TourResourceRequirement::query()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $activeTour->id,
            'resource_type' => 'vehicle',
            'is_required' => true,
            'min_units' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('operations-resources.index', ['required_by_active_tour' => '1']))
            ->assertOk()
            ->assertSee('Boat Active Type')
            ->assertDontSee('Equipment Non Active');
    }

    public function test_resource_update_can_sync_tour_usage_selection(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);
        $resource = TenantResource::query()->create([
            'tenant_id' => $tenant->id,
            'resource_type' => 'vehicle',
            'name' => 'Bus Sync Tour',
            'reference_code' => 'BUS-SYNC',
            'capacity' => 20,
            'status' => 'available',
        ]);
        $tourA = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tour A',
        ]);
        $tourB = Tour::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tour B',
        ]);

        TourResourceRequirement::query()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tourA->id,
            'resource_type' => 'vehicle',
            'is_required' => false,
            'min_units' => 1,
        ]);
        TourResourceRequirement::query()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tourB->id,
            'resource_type' => 'vehicle',
            'is_required' => true,
            'min_units' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('operations-resources.update', $resource), [
                'resource_type' => 'vehicle',
                'name' => 'Bus Sync Tour Rev',
                'reference_code' => 'BUS-SYNC-REV',
                'capacity' => 25,
                'sync_tour_usage' => '1',
                'tour_ids' => [(string) $tourA->id],
            ])
            ->assertRedirect(route('operations-resources.index'));

        $this->assertDatabaseHas('tour_resource_requirements', [
            'tenant_id' => $tenant->id,
            'tour_id' => $tourA->id,
            'resource_type' => 'vehicle',
            'is_required' => true,
        ]);
        $this->assertDatabaseHas('tour_resource_requirements', [
            'tenant_id' => $tenant->id,
            'tour_id' => $tourB->id,
            'resource_type' => 'vehicle',
            'is_required' => false,
            'min_units' => 2,
        ]);
    }
}
