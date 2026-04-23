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
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('channel', 50)->nullable()->after('status');
            $table->string('channel_order_id')->nullable()->after('channel');
            $table->char('currency', 3)->nullable()->after('channel_order_id');
            $table->decimal('gross_amount', 12, 2)->default(0)->after('currency');
            $table->decimal('commission_amount', 12, 2)->default(0)->after('gross_amount');
            $table->decimal('net_amount', 12, 2)->default(0)->after('commission_amount');
            $table->decimal('fx_rate_to_idr', 16, 6)->nullable()->after('net_amount');
            $table->decimal('revenue_amount', 14, 2)->default(0)->after('fx_rate_to_idr');
            $table->json('pricing_payload_json')->nullable()->after('revenue_amount');

            $table->index(['channel', 'status']);
            $table->index('channel_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['channel', 'status']);
            $table->dropIndex(['channel_order_id']);

            $table->dropColumn([
                'channel',
                'channel_order_id',
                'currency',
                'gross_amount',
                'commission_amount',
                'net_amount',
                'fx_rate_to_idr',
                'revenue_amount',
                'pricing_payload_json',
            ]);
        });
    }
};
