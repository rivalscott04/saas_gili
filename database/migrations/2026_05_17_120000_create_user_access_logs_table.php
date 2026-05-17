<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->char('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();

            $table->index(['accessed_at', 'country_code']);
            $table->index(['tenant_id', 'accessed_at']);
            $table->index(['user_id', 'ip_address', 'accessed_at'], 'user_access_logs_dedupe_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_logs');
    }
};
