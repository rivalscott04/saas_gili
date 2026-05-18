<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Tour;
use App\Models\User;
use App\Models\TourDayCapacity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GygProductionTestingSeeder extends Seeder
{
    public function run(): void
    {
        $supplierId = trim((string) config('gyg_supplier_api.supplier_id', 'Abc123'));
        if ($supplierId === '') {
            $supplierId = 'Abc123';
        }

        $tenant = Tenant::query()->updateOrCreate(
            ['code' => $supplierId],
            [
                'name' => 'Gili Snorkeling',
                'timezone' => 'Asia/Makassar',
                'is_active' => true,
                'max_users' => 10,
                'billing_cycle' => 'monthly',
                'subscription_status' => 'active',
            ]
        );

        // Dev impersonate needs at least one non-superadmin user per seeded tenant.
        User::query()->updateOrCreate(
            ['email' => 'tenant-admin@gili.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Gili Tenant Admin',
                'password' => 'password',
                'role' => 'tenant_admin',
                'status' => 'active',
                'subscription_status' => 'active',
                'seat_limit_reached' => false,
            ],
        );

        $products = [
            [
                'name' => 'Gili Snorkeling - Time Point (Individual)',
                'code' => 'prod123',
                'description' => 'GYG production testing: time point + individual.',
                'default_max_pax_per_day' => 12,
            ],
            [
                'name' => 'Gili Snorkeling - Time Point (GROUP)',
                'code' => 'prod124',
                'description' => 'GYG production testing: time point + group.',
                'default_max_pax_per_day' => 20,
            ],
            [
                'name' => 'Gili Snorkeling - Time PERIOD (Individual)',
                'code' => 'PPYM1U',
                'description' => 'GYG production testing: used for fetch addons.',
                'default_max_pax_per_day' => 15,
            ],
            [
                'name' => 'Gili Snorkeling - Time PERIOD (GROUP)',
                'code' => 'prod125',
                'description' => 'GYG production testing: time period + group.',
                'default_max_pax_per_day' => 25,
            ],
        ];

        $startDate = Carbon::today()->addDays(1);
        $rangeDays = 21;
        /** @var Collection<int, array{tour_id:int,service_date:string,tenant_id:int,max_pax:int,created_at:\Illuminate\Support\Carbon,updated_at:\Illuminate\Support\Carbon}> $capacityRows */
        $capacityRows = collect();
        $now = now();

        foreach ($products as $product) {
            $tour = Tour::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $product['code'],
                ],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'default_max_pax_per_day' => (int) $product['default_max_pax_per_day'],
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            for ($i = 0; $i < $rangeDays; $i++) {
                $serviceDate = $startDate->copy()->addDays($i)->toDateString();
                $capacityRows->push([
                    'tour_id' => (int) $tour->id,
                    'service_date' => $serviceDate,
                    'tenant_id' => (int) $tenant->id,
                    'max_pax' => (int) $tour->default_max_pax_per_day,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($capacityRows->isNotEmpty()) {
            TourDayCapacity::query()->upsert(
                $capacityRows->all(),
                ['tour_id', 'service_date'],
                ['tenant_id', 'max_pax', 'updated_at']
            );
        }
    }
}

