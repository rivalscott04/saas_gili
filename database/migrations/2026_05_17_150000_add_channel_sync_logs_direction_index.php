<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_sync_logs', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'created_at', 'direction', 'status'],
                'channel_sync_logs_tenant_created_dir_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('channel_sync_logs', function (Blueprint $table): void {
            $table->dropIndex('channel_sync_logs_tenant_created_dir_status_idx');
        });
    }
};
