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
            $table->string('confirmation_token_hash', 64)->nullable()->after('confirmation_token');
            $table->timestamp('confirmation_token_expires_at')->nullable()->after('confirmation_token_hash');
        });

        DB::table('bookings')
            ->whereNotNull('confirmation_token')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('bookings')->where('id', $row->id)->update([
                        'confirmation_token_hash' => hash('sha256', (string) $row->confirmation_token),
                        'confirmation_token_expires_at' => now()->addDays(30),
                        'confirmation_token' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['confirmation_token_hash', 'confirmation_token_expires_at']);
        });
    }
};
