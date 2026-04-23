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
        Schema::create('channel_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('travel_agent_id')->nullable()->constrained('travel_agents')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('direction', 32)->default('internal');
            $table->string('status', 32)->default('success');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'travel_agent_id', 'status'], 'channel_sync_logs_tenant_agent_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_sync_logs');
    }
};
