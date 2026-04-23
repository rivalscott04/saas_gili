<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 190);
            $table->string('code', 80)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('default_max_pax_per_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'code']);
            $table->index('tenant_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('tour_id')->nullable()->after('customer_id')->constrained('tours')->nullOnDelete();
            $table->index(['tenant_id', 'tour_id']);
        });

        $now = now();
        $pairs = DB::table('bookings')
            ->select('tenant_id', 'tour_name')
            ->whereNotNull('tenant_id')
            ->whereNotNull('tour_name')
            ->where('tour_name', '!=', '')
            ->distinct()
            ->orderBy('tenant_id')
            ->get();

        foreach ($pairs as $pair) {
            $tenantId = (int) $pair->tenant_id;
            $tourName = trim((string) $pair->tour_name);
            if ($tenantId <= 0 || $tourName === '') {
                continue;
            }

            $tourId = DB::table('tours')
                ->where('tenant_id', $tenantId)
                ->where('name', $tourName)
                ->value('id');

            if (! $tourId) {
                $tourId = DB::table('tours')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $tourName,
                    'code' => null,
                    'description' => null,
                    'default_max_pax_per_day' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('bookings')
                ->where('tenant_id', $tenantId)
                ->where('tour_name', $tourName)
                ->update(['tour_id' => $tourId]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'tour_id']);
            $table->dropConstrainedForeignId('tour_id');
        });

        Schema::dropIfExists('tours');
    }
};
