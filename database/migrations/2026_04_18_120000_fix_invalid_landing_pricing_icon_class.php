<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Class Remix salah eja; glyph tidak cocok dengan ikon buku. */
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('landing_pricing_plans')) {
            return;
        }

        DB::table('landing_pricing_plans')
            ->where('icon_class', 'ri-book-mark-line')
            ->update(['icon_class' => 'ri-book-line', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Tidak dibalik: bisa mengubah paket yang memang memilih ri-book-line.
    }
};
