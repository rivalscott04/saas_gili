<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_day_capacities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('max_pax');
            $table->timestamps();

            $table->unique(['tour_id', 'service_date']);
            $table->index(['tenant_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_day_capacities');
    }
};
