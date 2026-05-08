<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->resetSeedData();

        // Superadmin login (dev): email ends with .test — not .tes
        User::query()->updateOrCreate(
            ['email' => 'admin@desma.test'],
            [
                'tenant_id' => null,
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'superadmin',
            ],
        );

        $this->call(GygProductionTestingSeeder::class);
    }

    private function resetSeedData(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // Remove all tenant-related demo/self-test data.
        // We delete defensively because not every environment has every table.
        foreach ([
            'booking_status_events',
            'chat_messages',
            'bookings',
            'customers',
            'chat_templates',
            'tour_day_capacities',
            'tenant_resources',
            'tenant_roles',
            'tours',
            'tenants',
            'users',
        ] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
