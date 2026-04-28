<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('supplier_booking_reference', 25)
                ->nullable()
                ->after('channel_order_id');
            $table->unique('supplier_booking_reference', 'bookings_supplier_booking_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropUnique('bookings_supplier_booking_reference_unique');
            $table->dropColumn('supplier_booking_reference');
        });
    }
};
