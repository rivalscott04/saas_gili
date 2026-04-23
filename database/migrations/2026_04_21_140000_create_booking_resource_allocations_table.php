<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover from a previous failed run where CREATE succeeded but indexes failed,
        // leaving the table without a migrations row (MySQL "already exists" on re-run).
        Schema::dropIfExists('booking_resource_allocations');

        Schema::create('booking_resource_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('tenant_resource_id')->constrained('tenant_resources')->cascadeOnDelete();
            $table->date('allocation_date');
            $table->unsignedInteger('allocated_units')->nullable();
            $table->unsignedInteger('allocated_pax')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            // MySQL max identifier length is 64 chars; Laravel's auto-generated unique names exceed it here.
            $table->unique(['tenant_resource_id', 'allocation_date'], 'br_alloc_res_date_uniq');
            $table->unique(['booking_id', 'tenant_resource_id', 'allocation_date'], 'br_alloc_book_res_date_uniq');
            $table->index(['tenant_id', 'allocation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_resource_allocations');
    }
};
