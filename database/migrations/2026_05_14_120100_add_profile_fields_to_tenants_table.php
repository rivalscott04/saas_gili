<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'whatsapp_sender_number')) {
                $table->string('whatsapp_sender_number', 32)->nullable()->after('name');
            }
            if (! Schema::hasColumn('tenants', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('whatsapp_sender_number');
            }
            if (! Schema::hasColumn('tenants', 'address')) {
                $table->string('address')->nullable()->after('logo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            foreach (['address', 'logo_path', 'whatsapp_sender_number'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
