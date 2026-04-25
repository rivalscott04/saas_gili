<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('external_booking_ref', 191)->nullable()->after('channel_order_id');
            $table->string('external_activity_id', 191)->nullable()->after('external_booking_ref');
            $table->string('external_option_id', 191)->nullable()->after('external_activity_id');
            $table->string('external_status', 64)->nullable()->after('external_option_id');
            $table->string('sync_status', 64)->nullable()->after('external_status');
            $table->timestamp('last_synced_at')->nullable()->after('sync_status');
            $table->text('last_sync_error')->nullable()->after('last_synced_at');

            $table->index('external_booking_ref');
        });

        Schema::create('channel_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('travel_agent_id')->nullable()->constrained('travel_agents')->nullOnDelete();
            $table->string('dedupe_key', 191)->unique();
            $table->string('event_kind', 120)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_webhook_events');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['external_booking_ref']);
            $table->dropColumn([
                'external_booking_ref',
                'external_activity_id',
                'external_option_id',
                'external_status',
                'sync_status',
                'last_synced_at',
                'last_sync_error',
            ]);
        });
    }
};
