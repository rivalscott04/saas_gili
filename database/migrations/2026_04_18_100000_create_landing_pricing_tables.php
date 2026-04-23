<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->unsignedInteger('price_monthly');
            $table->unsignedInteger('price_yearly');
            $table->boolean('is_popular')->default(false);
            $table->unsignedInteger('max_users')->nullable()->comment('null = unlimited');
            $table->string('icon_class', 128)->default('ri-book-mark-line');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('landing_pricing_plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_pricing_plan_id')
                ->constrained('landing_pricing_plans')
                ->cascadeOnDelete();
            $table->string('display_text', 500);
            $table->boolean('is_included')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pricing_plan_features');
        Schema::dropIfExists('landing_pricing_plans');
    }
};
