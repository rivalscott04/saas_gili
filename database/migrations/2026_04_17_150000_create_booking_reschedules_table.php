<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reschedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->enum('requested_by', ['customer', 'operator', 'system'])->default('customer');
            $table->string('request_source', 64)->nullable();
            $table->enum('workflow_status', ['requested', 'reviewed', 'approved', 'rejected', 'completed'])->default('requested');
            $table->dateTime('old_tour_start_at')->nullable();
            $table->dateTime('requested_tour_start_at')->nullable();
            $table->dateTime('final_tour_start_at')->nullable();
            $table->string('requested_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'workflow_status']);
            $table->index(['requested_by', 'request_source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reschedules');
    }
};
