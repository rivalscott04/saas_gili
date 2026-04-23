<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pricing_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('landing_pricing_plans', 'category_slots_included')) {
                $table->unsignedTinyInteger('category_slots_included')->default(1)->after('sort_order');
            }
            if (! Schema::hasColumn('landing_pricing_plans', 'extra_category_price_monthly')) {
                $table->unsignedInteger('extra_category_price_monthly')->default(0)->after('category_slots_included');
            }
            if (! Schema::hasColumn('landing_pricing_plans', 'extra_category_price_yearly')) {
                $table->unsignedInteger('extra_category_price_yearly')->default(0)->after('extra_category_price_monthly');
            }
        });

        Schema::dropIfExists('landing_pricing_plan_allowed_categories');

        Schema::create('landing_pricing_plan_allowed_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('landing_pricing_plan_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('landing_pricing_plan_id', 'lp_acl_plan_fk')
                ->references('id')
                ->on('landing_pricing_plans')
                ->cascadeOnDelete();
            $table->foreign('category_id', 'lp_acl_cat_fk')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
            $table->unique(['landing_pricing_plan_id', 'category_id'], 'lp_plan_cat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pricing_plan_allowed_categories');
        Schema::table('landing_pricing_plans', function (Blueprint $table): void {
            $table->dropColumn([
                'category_slots_included',
                'extra_category_price_monthly',
                'extra_category_price_yearly',
            ]);
        });
    }
};
