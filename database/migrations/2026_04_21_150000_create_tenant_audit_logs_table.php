<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->date('service_date')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'tour_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_audit_logs');
    }
};
