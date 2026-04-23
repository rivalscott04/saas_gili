<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status', 32)->default('active')->after('role');
            $table->string('subscription_status', 32)->default('active')->after('status');
            $table->boolean('seat_limit_reached')->default(false)->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['status', 'subscription_status', 'seat_limit_reached']);
        });
    }
};
