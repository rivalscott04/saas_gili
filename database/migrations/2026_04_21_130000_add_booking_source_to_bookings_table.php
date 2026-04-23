<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('booking_source', 20)->default('manual')->after('status');
            $table->index('booking_source');
        });

        DB::table('bookings')
            ->where(function ($query): void {
                $query->whereNull('channel')
                    ->orWhereIn(DB::raw('LOWER(channel)'), ['manual', 'direct']);
            })
            ->update(['booking_source' => 'manual']);

        DB::table('bookings')
            ->whereNotNull('channel')
            ->whereNotIn(DB::raw('LOWER(channel)'), ['manual', 'direct'])
            ->update(['booking_source' => 'ota']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['booking_source']);
            $table->dropColumn('booking_source');
        });
    }
};
