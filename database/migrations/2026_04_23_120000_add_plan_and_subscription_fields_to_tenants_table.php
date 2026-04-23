<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('landing_pricing_plan_id')
                ->nullable()
                ->after('max_users')
                ->constrained('landing_pricing_plans')
                ->nullOnDelete();
            $table->string('billing_cycle', 16)->default('monthly')->after('landing_pricing_plan_id');
            $table->string('subscription_status', 32)->default('active')->after('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('landing_pricing_plan_id');
            $table->dropColumn(['billing_cycle', 'subscription_status']);
        });
    }
};
