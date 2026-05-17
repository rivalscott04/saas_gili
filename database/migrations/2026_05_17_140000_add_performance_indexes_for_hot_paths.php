<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_status_events', function (Blueprint $table): void {
            $table->index(['booking_id', 'reason', 'created_at'], 'booking_status_events_booking_reason_created_idx');
        });

        Schema::table('booking_reschedules', function (Blueprint $table): void {
            $table->index(['booking_id', 'created_at'], 'booking_reschedules_booking_created_idx');
        });

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->index(['booking_id', 'sender', 'created_at'], 'chat_messages_booking_sender_created_idx');
        });

        Schema::table('channel_sync_logs', function (Blueprint $table): void {
            $table->index(['tenant_id', 'created_at'], 'channel_sync_logs_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('booking_status_events', function (Blueprint $table): void {
            $table->dropIndex('booking_status_events_booking_reason_created_idx');
        });

        Schema::table('booking_reschedules', function (Blueprint $table): void {
            $table->dropIndex('booking_reschedules_booking_created_idx');
        });

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropIndex('chat_messages_booking_sender_created_idx');
        });

        Schema::table('channel_sync_logs', function (Blueprint $table): void {
            $table->dropIndex('channel_sync_logs_tenant_created_idx');
        });
    }
};
