<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * GYG testing seeder (and legacy setup) can create tenants without any user row.
 * Registration always creates tenant_admin; this backfills the same for orphan tenants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Tenant::query()
            ->whereDoesntHave('users')
            ->orderBy('id')
            ->each(function (Tenant $tenant): void {
                $code = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $tenant->code) ?? 'tenant');
                $code = trim($code, '-') ?: 'tenant';

                User::query()->updateOrCreate(
                    ['email' => 'admin+'.$code.'@tenant.dev'],
                    [
                        'tenant_id' => $tenant->id,
                        'name' => trim((string) $tenant->name) !== ''
                            ? trim((string) $tenant->name).' Admin'
                            : 'Tenant Admin',
                        'password' => 'password',
                        'role' => 'tenant_admin',
                        'status' => 'active',
                        'subscription_status' => 'active',
                        'seat_limit_reached' => false,
                    ],
                );
            });
    }

    public function down(): void
    {
        // Do not delete auto-provisioned admins; may be in use.
    }
};
