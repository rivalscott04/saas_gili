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
        Schema::create('tenant_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 40);
            $table->string('name', 160);
            $table->string('reference_code', 120)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 40)->default('available');
            $table->timestamp('blocked_from')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->string('block_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'resource_type'], 'tenant_resources_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_resources');
    }
};
