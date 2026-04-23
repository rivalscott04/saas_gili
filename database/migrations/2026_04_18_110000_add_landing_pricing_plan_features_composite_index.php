<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pricing_plan_features', function (Blueprint $table) {
            $table->index(['landing_pricing_plan_id', 'sort_order'], 'landing_plan_features_plan_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pricing_plan_features', function (Blueprint $table) {
            $table->dropIndex('landing_plan_features_plan_sort_idx');
        });
    }
};
