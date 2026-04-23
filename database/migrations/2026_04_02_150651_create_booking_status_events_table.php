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
        Schema::create('booking_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->string('changed_by')->default('system');
            $table->string('reason')->nullable();
            $table->string('source')->nullable();
            $table->string('source_message_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('new_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_status_events');
    }
};
