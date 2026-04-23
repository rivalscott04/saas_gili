<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tour_resource_requirements');

        Schema::create('tour_resource_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->string('resource_type', 40);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('min_units')->default(1);
            $table->timestamps();

            $table->unique(['tour_id', 'resource_type'], 'tour_req_tour_type_uniq');
            $table->index(['tenant_id', 'resource_type'], 'tour_req_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_resource_requirements');
    }
};
