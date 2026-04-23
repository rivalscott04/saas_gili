<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantUsersSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_cannot_override_tenant_scope_via_query_string(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $tenantAdmin = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'tenant_admin',
        ]);

        $this->actingAs($tenantAdmin)
            ->get(route('tenant-users.index', ['tenant' => $tenantB->code]))
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_override_tenant_scope_via_payload(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $tenantAdmin = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'tenant_admin',
        ]);

        $this->actingAs($tenantAdmin)
            ->post(route('tenant-users.store'), [
                'tenant_code' => $tenantB->code,
                'name' => 'Injected Tenant User',
                'email' => 'injected@example.com',
                'role' => 'tenant_admin',
                'password' => 'password123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'injected@example.com',
        ]);
    }

    public function test_superadmin_gets_not_found_when_tenant_slug_is_invalid(): void
    {
        $superadmin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'superadmin',
        ]);

        $this->actingAs($superadmin)
            ->get(route('tenant-users.index', ['tenant' => 'unknown-tenant']))
            ->assertNotFound();
    }

    public function test_tenant_admin_cannot_create_user_with_blank_name(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantAdmin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
        ]);

        $this->actingAs($tenantAdmin)
            ->from(route('tenant-users.index'))
            ->post(route('tenant-users.store'), [
                'name' => '   ',
                'email' => 'blank-name@example.com',
                'role' => 'tenant_admin',
                'password' => 'password123',
            ])
            ->assertRedirect(route('tenant-users.index'));

        $this->assertDatabaseMissing('users', [
            'email' => 'blank-name@example.com',
        ]);
    }
}
