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
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('notes');
            $table->string('assigned_to_name')->nullable()->after('internal_notes');
            $table->json('tags')->nullable()->after('assigned_to_name');
            $table->boolean('needs_attention')->default(false)->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['internal_notes', 'assigned_to_name', 'tags', 'needs_attention']);
        });
    }
};
