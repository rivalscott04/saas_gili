<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_onboarding_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            // Mode lama (ota_only / mixed) dihapus — produk tidak mendukung OTA tanpa aplikasi
            // (lihat docs/ux-review/2026-05-14-tenant-onboarding-plan.md §1 + §4.2).
            $table->enum('mode', ['two_way_sync', 'app_only'])->nullable();
            $table->timestamp('dismissed_at')->nullable();
            // step_completed_at: snapshot kapan tiap step pertama kali "done".
            // Status aktual tetap dihitung on the fly dari data nyata (§4.3 plan).
            $table->json('step_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_onboarding_states');
    }
};
