<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tour_name');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->dateTime('tour_start_at');
            $table->string('location')->nullable();
            $table->string('guide_name')->nullable();
            $table->enum('status', ['standby', 'confirmed', 'pending', 'cancelled'])->default('pending');
            $table->unsignedInteger('participants')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('tour_start_at');
            $table->index('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
