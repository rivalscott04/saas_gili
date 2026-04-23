<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCategoryAndTourDayCapacityWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_update_tenant_categories(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $catA = Category::query()->create(['code' => 'seg-a', 'name' => 'Segment A', 'is_active' => true]);
        $catB = Category::query()->create(['code' => 'seg-b', 'name' => 'Segment B', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('tenant-categories.update'), [
                'category_ids' => [(int) $catA->id, (int) $catB->id],
            ])
            ->assertRedirect(route('tenant-categories.index'));

        $this->assertEqualsCanonicalizing(
            [(int) $catA->id, (int) $catB->id],
            $tenant->fresh()->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_superadmin_must_send_tenant_code_when_updating_categories(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'acme']);
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);
        $cat = Category::query()->create(['code' => 'seg-x', 'name' => 'Segment X', 'is_active' => true]);

        $this->actingAs($superadmin)
            ->post(route('tenant-categories.update'), [
                'category_ids' => [(int) $cat->id],
            ])
            ->assertSessionHasErrors('tenant_code');

        $this->actingAs($superadmin)
            ->post(route('tenant-categories.update'), [
                'tenant_code' => $tenant->code,
                'category_ids' => [(int) $cat->id],
            ])
            ->assertRedirect(route('tenant-categories.index', ['tenant' => $tenant->code]));

        $this->assertTrue($tenant->fresh()->categories()->whereKey($cat->id)->exists());
    }

    public function test_tenant_admin_can_upsert_and_delete_tour_day_capacity(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenant->id,
        ]);
        $tour = Tour::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('tour-day-capacities.store'), [
                'tour_id' => $tour->id,
                'service_date' => '2026-06-10',
                'max_pax' => 22,
            ])
            ->assertRedirect(route('tour-day-capacities.index', ['tour_id' => $tour->id]));

        $row = TourDayCapacity::query()->where('tour_id', $tour->id)->firstOrFail();
        $this->assertSame(22, (int) $row->max_pax);

        $this->actingAs($admin)
            ->post(route('tour-day-capacities.store'), [
                'tour_id' => $tour->id,
                'service_date' => '2026-06-10',
                'max_pax' => 18,
            ])
            ->assertRedirect(route('tour-day-capacities.index', ['tour_id' => $tour->id]));

        $this->assertSame(18, (int) $row->fresh()->max_pax);

        $this->actingAs($admin)
            ->post(route('tour-day-capacities.destroy', $row))
            ->assertRedirect(route('tour-day-capacities.index', ['tour_id' => $tour->id]));

        $this->assertNull(TourDayCapacity::query()->whereKey($row->id)->first());
    }

    public function test_superadmin_must_send_tenant_code_when_deleting_capacity(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'acme']);
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);
        $tour = Tour::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        $row = TourDayCapacity::query()->create([
            'tenant_id' => $tenant->id,
            'tour_id' => $tour->id,
            'service_date' => '2026-07-01',
            'max_pax' => 5,
        ]);

        $this->actingAs($superadmin)
            ->post(route('tour-day-capacities.destroy', $row))
            ->assertSessionHasErrors('tenant_code');

        $this->actingAs($superadmin)
            ->post(route('tour-day-capacities.destroy', $row), [
                'tenant_code' => $tenant->code,
            ])
            ->assertRedirect(route('tour-day-capacities.index', [
                'tenant' => $tenant->code,
                'tour_id' => $tour->id,
            ]));

        $this->assertNull(TourDayCapacity::query()->whereKey($row->id)->first());
    }

    public function test_tenant_admin_cannot_delete_capacity_from_other_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->create([
            'role' => 'tenant_admin',
            'tenant_id' => $tenantA->id,
        ]);
        $tourB = Tour::factory()->create(['tenant_id' => $tenantB->id, 'is_active' => true]);
        $row = TourDayCapacity::query()->create([
            'tenant_id' => $tenantB->id,
            'tour_id' => $tourB->id,
            'service_date' => '2026-08-01',
            'max_pax' => 9,
        ]);

        $this->actingAs($adminA)
            ->post(route('tour-day-capacities.destroy', $row))
            ->assertForbidden();
    }
}
