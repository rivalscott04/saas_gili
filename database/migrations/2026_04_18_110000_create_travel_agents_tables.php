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
        Schema::create('travel_agents', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('signup_url')->nullable();
            $table->string('docs_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_travel_agent_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('travel_agent_id')->constrained('travel_agents')->cascadeOnDelete();
            $table->string('status', 32)->default('disconnected');
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('account_reference', 191)->nullable();
            $table->json('extra_config')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'travel_agent_id'], 'tenant_travel_agent_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_travel_agent_connections');
        Schema::dropIfExists('travel_agents');
    }
};
