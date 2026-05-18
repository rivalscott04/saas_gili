<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration 2026_04_17_140000 mapped every role=admin to superadmin, including tenant admins.
 * Those users still have tenant_id set; impersonation must treat them as tenant users again.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('tenant_id')
            ->where('role', 'superadmin')
            ->update(['role' => 'tenant_admin']);
    }

    public function down(): void
    {
        // Cannot safely distinguish platform superadmin from restored tenant rows.
    }
};
