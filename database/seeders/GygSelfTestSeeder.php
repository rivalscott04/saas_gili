<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GygSelfTestSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['code' => 'Abc123'],
            [
                'name' => 'GYG Self Test Supplier',
                'timezone' => 'Asia/Makassar',
                'is_active' => true,
                'max_users' => 10,
                'billing_cycle' => 'monthly',
                'subscription_status' => 'active',
            ]
        );

        $products = [
            [
                'name' => 'GYG Time Point Individual',
                'code' => 'GYG-TP-IND',
                'description' => 'Self-test: time point + individual',
                'default_max_pax_per_day' => 12,
            ],
            [
                'name' => 'GYG Time Point Group',
                'code' => 'GYG-TP-GRP',
                'description' => 'Self-test: time point + group',
                'default_max_pax_per_day' => 12,
            ],
            [
                'name' => 'GYG Time Period Individual',
                'code' => 'GYG-TR-IND',
                'description' => 'Self-test: time period + individual',
                'default_max_pax_per_day' => 15,
            ],
            [
                'name' => 'GYG Time Period Group',
                'code' => 'GYG-TR-GRP',
                'description' => 'Self-test: time period + group',
                'default_max_pax_per_day' => 15,
            ],
        ];

        $startDate = Carbon::today()->addDays(1);
        $availableRangeDays = 6; // enough room for GYG to test booking change flow
        $notAvailableRangeDays = 2;

        foreach ($products as $product) {
            $tour = Tour::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $product['code'],
                ],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'default_max_pax_per_day' => $product['default_max_pax_per_day'],
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            // Available dates: positive capacity
            for ($i = 0; $i < $availableRangeDays; $i++) {
                $serviceDate = $startDate->copy()->addDays($i)->toDateString();
                TourDayCapacity::query()->updateOrCreate(
                    [
                        'tour_id' => $tour->id,
                        'service_date' => $serviceDate,
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'max_pax' => $product['default_max_pax_per_day'],
                    ]
                );
            }

            // Not available dates: zero capacity
            for ($i = 0; $i < $notAvailableRangeDays; $i++) {
                $serviceDate = $startDate->copy()->addDays($availableRangeDays + $i)->toDateString();
                TourDayCapacity::query()->updateOrCreate(
                    [
                        'tour_id' => $tour->id,
                        'service_date' => $serviceDate,
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'max_pax' => 0,
                    ]
                );
            }
        }

        // Seed one real booking so vacancies are reduced on first available day.
        $leadCustomer = Customer::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => 'gyg-selftest@example.com',
            ],
            [
                'external_source' => 'getyourguide',
                'external_customer_ref' => 'GYG-SEED-CUST-001',
                'full_name' => 'GYG Self Test Customer',
                'phone' => '+62000000001',
                'country_code' => 'ID',
                'raw_payload' => ['seed' => 'gyg-self-test'],
            ]
        );

        $individualTour = Tour::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'GYG-TP-IND')
            ->first();

        if ($individualTour) {
            Booking::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'supplier_booking_reference' => 'bkGYGSEED001',
                ],
                [
                    'customer_id' => $leadCustomer->id,
                    'tour_id' => $individualTour->id,
                    'tour_name' => $individualTour->name,
                    'customer_name' => $leadCustomer->full_name,
                    'customer_email' => $leadCustomer->email,
                    'customer_phone' => $leadCustomer->phone,
                    'tour_start_at' => $startDate->copy()->setTime(9, 0)->toDateTimeString(),
                    'status' => 'confirmed',
                    'booking_source' => 'ota',
                    'channel' => 'GETYOURGUIDE',
                    'channel_order_id' => 'GYG-SEED-ORDER-001',
                    'external_booking_ref' => 'GYG-SEED-ORDER-001',
                    'external_option_id' => $individualTour->code,
                    'currency' => 'EUR',
                    'participants' => 2,
                    'notes' => 'Seed booking for GYG self-test vacancy checks.',
                ]
            );
        }
    }
}
