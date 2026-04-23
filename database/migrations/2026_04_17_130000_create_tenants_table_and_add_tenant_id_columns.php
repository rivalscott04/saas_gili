<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('timezone', 64)->default('Asia/Makassar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'email']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'tour_start_at']);
        });

        Schema::table('chat_templates', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->index('tenant_id');
        });

        $tenantId = DB::table('tenants')->insertGetId([
            'code' => 'default',
            'name' => 'Default Tenant',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        DB::table('customers')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        DB::table('bookings')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        DB::table('chat_templates')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
    }

    public function down(): void
    {
        Schema::table('chat_templates', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'tour_start_at']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::dropIfExists('tenants');
    }
};
