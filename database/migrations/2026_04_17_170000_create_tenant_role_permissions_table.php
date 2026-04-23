<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role', 64);
            $table->string('permission_key', 128);
            $table->boolean('is_allowed')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'role', 'permission_key'], 'tenant_role_permission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_role_permissions');
    }
};
