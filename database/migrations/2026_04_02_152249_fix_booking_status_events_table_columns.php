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
        if (! Schema::hasColumn('booking_status_events', 'booking_id')) {
            Schema::table('booking_status_events', function (Blueprint $table): void {
                $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('booking_status_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_status_events', 'old_status')) {
                $table->string('old_status')->nullable();
            }
            if (! Schema::hasColumn('booking_status_events', 'new_status')) {
                $table->string('new_status')->nullable();
            }
            if (! Schema::hasColumn('booking_status_events', 'changed_by')) {
                $table->string('changed_by')->default('system');
            }
            if (! Schema::hasColumn('booking_status_events', 'reason')) {
                $table->string('reason')->nullable();
            }
            if (! Schema::hasColumn('booking_status_events', 'source')) {
                $table->string('source')->nullable();
            }
            if (! Schema::hasColumn('booking_status_events', 'source_message_id')) {
                $table->string('source_message_id')->nullable();
            }
            if (! Schema::hasColumn('booking_status_events', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_status_events', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_status_events', 'booking_id')) {
                $table->dropConstrainedForeignId('booking_id');
            }
            $dropColumns = [];
            foreach (['old_status', 'new_status', 'changed_by', 'reason', 'source', 'source_message_id', 'metadata'] as $column) {
                if (Schema::hasColumn('booking_status_events', $column)) {
                    $dropColumns[] = $column;
                }
            }
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
