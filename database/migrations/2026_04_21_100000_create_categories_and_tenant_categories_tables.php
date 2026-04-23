<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'category_id']);
            $table->index(['category_id', 'tenant_id']);
        });

        $now = now();
        $tourOperatorId = DB::table('categories')->insertGetId([
            'code' => 'tour_operator',
            'name' => 'Tour Operator',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('categories')->insert([
            'code' => 'vehicle_rental',
            'name' => 'Vehicle Rental',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tenantIds = DB::table('tenants')->pluck('id');
        $rows = $tenantIds->map(fn (int $tenantId): array => [
            'tenant_id' => $tenantId,
            'category_id' => $tourOperatorId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('tenant_categories')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_categories');
        Schema::dropIfExists('categories');
    }
};
