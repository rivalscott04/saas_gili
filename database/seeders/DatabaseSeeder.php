<?php

namespace Database\Seeders;

use App\Models\LandingPricingPlan;
use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ChatMessage;
use App\Models\ChatTemplate;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TenantRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const ENGLISH_CHAT_MESSAGES = [
        'Hi, I would like to confirm my booking details.',
        'Could you please share the exact meeting point?',
        'Thank you, that schedule works for us.',
        'We are on our way and should arrive on time.',
        'Can we request hotel pickup for this tour?',
        'Perfect, see you tomorrow morning.',
        'Please let me know if anything changes.',
        'I have completed the payment, thank you.',
        'Thanks for the update, we are ready.',
        'Could you confirm the pickup time once again?',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->resetSeedData();
        $this->call(LandingPricingSeeder::class);
        User::query()->updateOrCreate(
            ['email' => 'admin@gilitour.test'],
            [
                'tenant_id' => null,
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'superadmin',
            ],
        );
        $defaultPlanId = LandingPricingPlan::query()
            ->where('is_popular', true)
            ->value('id');
        if ($defaultPlanId === null) {
            $defaultPlanId = LandingPricingPlan::query()->orderBy('sort_order')->value('id');
        }

        $tenantSeedConfigs = [
            [
                'code' => 'gilitour-default',
                'name' => 'Gili Tour Default Tenant',
                'timezone' => 'Asia/Makassar',
                'max_users' => 5,
                'admin' => [
                    'name' => 'Tenant Admin East',
                    'email' => 'tenant.east@gilitour.test',
                ],
                'guides' => [
                    ['name' => 'Guide East 1', 'email' => 'guide.east.1@gilitour.test'],
                    ['name' => 'Guide East 2', 'email' => 'guide.east.2@gilitour.test'],
                ],
                'customers' => [
                    ['name' => 'Ethan Brooks', 'email' => 'ethan.brooks@example.com', 'phone' => '+1-202-555-0101', 'source' => 'getyourguide', 'country' => 'US'],
                    ['name' => 'Charlotte Hayes', 'email' => 'charlotte.hayes@example.com', 'phone' => '+44-20-7946-0123', 'source' => 'manual', 'country' => 'GB'],
                    ['name' => 'Oliver Bennett', 'email' => 'oliver.bennett@example.com', 'phone' => '+61-2-9012-4455', 'source' => 'getyourguide', 'country' => 'AU'],
                    ['name' => 'Amelia Foster', 'email' => 'amelia.foster@example.com', 'phone' => '+49-30-1234-5678', 'source' => 'whatsapp-import', 'country' => 'DE'],
                    ['name' => 'Henry Collins', 'email' => 'henry.collins@example.com', 'phone' => '+33-1-8456-7788', 'source' => 'manual', 'country' => 'FR'],
                    ['name' => 'Grace Mitchell', 'email' => 'grace.mitchell@example.com', 'phone' => '+39-06-9988-2211', 'source' => 'getyourguide', 'country' => 'IT'],
                    ['name' => 'Jack Sullivan', 'email' => 'jack.sullivan@example.com', 'phone' => '+34-91-558-7744', 'source' => 'manual', 'country' => 'ES'],
                    ['name' => 'Lily Reynolds', 'email' => 'lily.reynolds@example.com', 'phone' => '+31-20-665-9988', 'source' => 'whatsapp-import', 'country' => 'NL'],
                    ['name' => 'Samuel Parker', 'email' => 'samuel.parker@example.com', 'phone' => '+65-6123-4455', 'source' => 'getyourguide', 'country' => 'SG'],
                    ['name' => 'Chloe Morgan', 'email' => 'chloe.morgan@example.com', 'phone' => '+1-310-555-0134', 'source' => 'manual', 'country' => 'US'],
                ],
            ],
            [
                'code' => 'gilitour-west',
                'name' => 'Gili Tour West Tenant',
                'timezone' => 'Asia/Jakarta',
                'max_users' => 5,
                'admin' => [
                    'name' => 'Tenant Admin West',
                    'email' => 'tenant.west@gilitour.test',
                ],
                'guides' => [
                    ['name' => 'Guide West 1', 'email' => 'guide.west.1@gilitour.test'],
                    ['name' => 'Guide West 2', 'email' => 'guide.west.2@gilitour.test'],
                ],
                'customers' => [
                    ['name' => 'Noah Walker', 'email' => 'noah.walker@example.com', 'phone' => '+1-415-555-0191', 'source' => 'manual', 'country' => 'US'],
                    ['name' => 'Sophia Carter', 'email' => 'sophia.carter@example.com', 'phone' => '+44-20-7123-4567', 'source' => 'getyourguide', 'country' => 'GB'],
                    ['name' => 'Lucas Turner', 'email' => 'lucas.turner@example.com', 'phone' => '+61-3-8899-1020', 'source' => 'manual', 'country' => 'AU'],
                    ['name' => 'Isla Campbell', 'email' => 'isla.campbell@example.com', 'phone' => '+49-89-3344-6677', 'source' => 'whatsapp-import', 'country' => 'DE'],
                    ['name' => 'Mason Hughes', 'email' => 'mason.hughes@example.com', 'phone' => '+33-1-5566-7788', 'source' => 'getyourguide', 'country' => 'FR'],
                    ['name' => 'Mia Cooper', 'email' => 'mia.cooper@example.com', 'phone' => '+39-02-7788-9900', 'source' => 'manual', 'country' => 'IT'],
                    ['name' => 'Logan Stewart', 'email' => 'logan.stewart@example.com', 'phone' => '+34-93-4455-6677', 'source' => 'manual', 'country' => 'ES'],
                    ['name' => 'Ava Simmons', 'email' => 'ava.simmons@example.com', 'phone' => '+31-10-5566-8899', 'source' => 'whatsapp-import', 'country' => 'NL'],
                    ['name' => 'James Perry', 'email' => 'james.perry@example.com', 'phone' => '+65-6234-5566', 'source' => 'getyourguide', 'country' => 'SG'],
                    ['name' => 'Ella Richardson', 'email' => 'ella.richardson@example.com', 'phone' => '+1-646-555-0165', 'source' => 'manual', 'country' => 'US'],
                ],
            ],
        ];

        $tours = [
            'Gili Trawangan Snorkeling Escape',
            'Gili Air Coral Garden Tour',
            'Gili Meno Turtle Point Adventure',
            '3 Gili Island Hopping',
            'Gili Trawangan Cycling & Sunset Point',
            'Gili Air Freedive Starter Trip',
            'Gili Meno Private Beach Picnic',
            'Lombok Selatan Beach Discovery',
            'Rinjani Foothill Sunrise Trek',
            'Senggigi Sunset Cruise',
            'Kuta Mandalika Coastal Trip',
            'Sendang Gile & Tiu Kelep Waterfall Trek',
            'Benang Kelambu and Benang Stokel Waterfall Tour',
            'Jeruk Manis Waterfall Nature Walk',
            'Mangku Sakti Waterfall Adventure',
            'Sembalun Highland & Pergasingan Viewpoint',
            'Tetebatu Rice Terrace and Village Tour',
        ];
        $locations = [
            'Bangsal Harbor',
            'Teluk Nare Pier',
            'Gili Trawangan Port',
            'Gili Air Harbor',
            'Gili Meno Jetty',
            'Senggigi Meeting Point',
            'Kuta Mandalika Pickup Point',
            'Mataram City Center',
            'Senaru Waterfall Gate',
            'Aik Berik Village Basecamp',
            'Tetebatu Main Junction',
            'Sembalun Valley Point',
        ];
        $guides = ['Rahman', 'Lalu', 'Ari', 'Teguh', 'Fadli', 'Zul', 'Irfan'];
        $statuses = ['standby', 'confirmed', 'pending'];
        $guideTeams = ['Guide Team A', 'Guide Team B', 'Guide Team C'];
        $bookingChannels = [
            ['code' => 'getyourguide', 'currency' => 'EUR', 'rate_to_idr' => 17500, 'commission_rate' => 0.22],
            ['code' => 'viator', 'currency' => 'USD', 'rate_to_idr' => 15800, 'commission_rate' => 0.20],
            ['code' => 'klook', 'currency' => 'USD', 'rate_to_idr' => 15800, 'commission_rate' => 0.18],
            ['code' => 'direct', 'currency' => 'IDR', 'rate_to_idr' => 1, 'commission_rate' => 0.00],
            ['code' => 'manual', 'currency' => 'IDR', 'rate_to_idr' => 1, 'commission_rate' => 0.00],
        ];
        $seedStartDate = Carbon::create(2026, 4, 20, 8, 0, 0);

        foreach ($tenantSeedConfigs as $tenantIndex => $tenantConfig) {
            $tenant = Tenant::query()->updateOrCreate(
                ['code' => $tenantConfig['code']],
                [
                    'name' => $tenantConfig['name'],
                    'timezone' => $tenantConfig['timezone'],
                    'is_active' => true,
                    'max_users' => $tenantConfig['max_users'],
                    'landing_pricing_plan_id' => $defaultPlanId,
                    'billing_cycle' => 'monthly',
                    'subscription_status' => 'active',
                ],
            );

            foreach ([
                ['code' => 'tenant_admin', 'name' => 'Tenant Admin', 'is_system' => true],
                ['code' => 'operator', 'name' => 'Operator', 'is_system' => true],
                ['code' => 'guide', 'name' => 'Guide', 'is_system' => true],
            ] as $role) {
                TenantRole::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $role['code'],
                    ],
                    [
                        'name' => $role['name'],
                        'is_system' => $role['is_system'],
                    ]
                );
            }

            $tenantAdmin = User::query()->updateOrCreate(
                ['email' => $tenantConfig['admin']['email']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $tenantConfig['admin']['name'],
                    'password' => 'password',
                    'role' => 'tenant_admin',
                ],
            );

            $guideUsers = collect($tenantConfig['guides'])->map(
                fn (array $guideData) => User::query()->updateOrCreate(
                    ['email' => $guideData['email']],
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $guideData['name'],
                        'password' => 'password',
                        'role' => 'guide',
                    ],
                )
            );

            $tenantToursByName = collect($tours)->mapWithKeys(function (string $tourName) use ($tenant) {
                $tour = Tour::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $tourName,
                    ],
                    [
                        'code' => null,
                        'description' => null,
                        'default_max_pax_per_day' => null,
                        'is_active' => true,
                        'sort_order' => 0,
                    ]
                );

                return [$tourName => $tour];
            });

            ChatTemplate::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Booking Reminder'],
                ['content' => '{{greeting}} {{customerName}}! Friendly reminder: you already have {{tourName}} booked. Please let us know if you are still joining us on the day, or if anything changed. Thanks!']
            );
            ChatTemplate::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Thank You'],
                ['content' => '{{greeting}} {{customerName}}, thanks for choosing us! We hope you enjoyed the {{tourName}}.']
            );
            ChatTemplate::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Payment Request'],
                ['content' => '{{greeting}} {{customerName}}, please kindly complete the payment for your {{tourName}} booking at your earliest convenience.']
            );

            $customers = collect($tenantConfig['customers'])->map(function (array $item) use ($tenant) {
                return Customer::query()->create([
                    'tenant_id' => $tenant->id,
                    'external_source' => $item['source'],
                    'external_customer_ref' => 'SRC-'.strtoupper(substr($item['source'], 0, 3)).'-'.fake()->unique()->numberBetween(10000, 99999),
                    'full_name' => $item['name'],
                    'email' => $item['email'],
                    'phone' => $item['phone'],
                    'country_code' => $item['country'],
                    'raw_payload' => ['seed' => true, 'channel' => $item['source']],
                ]);
            });

            $customers->each(function (Customer $customer, int $index) use ($tenantAdmin, $guideUsers, $tenant, $tenantIndex, $tours, $locations, $guides, $statuses, $guideTeams, $bookingChannels, $seedStartDate, $tenantToursByName): void {
                $globalIndex = ($tenantIndex * 10) + $index;
                $tourStart = $seedStartDate->copy()->addDays($globalIndex)->setTime(8 + ($globalIndex % 4), 0);
                $status = $statuses[$globalIndex % count($statuses)];
                $needsAttention = $tourStart->isBetween(now(), now()->addDays(3));
                $assignedUser = $index % 2 === 0 ? $guideUsers->first() : $tenantAdmin;
                $participants = ($globalIndex % 4) + 1;
                $channelData = $bookingChannels[$globalIndex % count($bookingChannels)];
                $grossAmount = ($participants * (65 + ($globalIndex % 3) * 15));
                $commissionAmount = $grossAmount * (float) $channelData['commission_rate'];
                $netAmount = max(0, $grossAmount - $commissionAmount);
                $fxRateToIdr = (float) $channelData['rate_to_idr'];
                $revenueAmount = $netAmount * $fxRateToIdr;
                $isExternalChannel = ! in_array($channelData['code'], ['direct', 'manual'], true);
                $channelOrderId = $isExternalChannel
                    ? strtoupper($channelData['code']).'-ORD-'.str_pad((string) ($globalIndex + 1001), 5, '0', STR_PAD_LEFT)
                    : null;
                $bookingSource = $isExternalChannel ? 'ota' : 'manual';

                $selectedTourName = $tours[$globalIndex % count($tours)];
                $selectedTour = $tenantToursByName->get($selectedTourName);

                $booking = Booking::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $assignedUser?->id,
                    'customer_id' => $customer->id,
                    'tour_id' => $selectedTour?->id,
                    'tour_name' => $selectedTourName,
                    'customer_name' => $customer->full_name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'tour_start_at' => $tourStart,
                    'location' => $locations[$globalIndex % count($locations)],
                    'guide_name' => $guides[$globalIndex % count($guides)],
                    'status' => $status,
                    'booking_source' => $bookingSource,
                    'participants' => $participants,
                    'channel' => $channelData['code'],
                    'channel_order_id' => $channelOrderId,
                    'currency' => $channelData['currency'],
                    'gross_amount' => $grossAmount,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $netAmount,
                    'fx_rate_to_idr' => $fxRateToIdr,
                    'revenue_amount' => $revenueAmount,
                    'pricing_payload_json' => [
                        'seed' => true,
                        'channel' => $channelData['code'],
                        'commission_rate' => $channelData['commission_rate'],
                    ],
                    'notes' => 'Seeded booking data for demo environment.',
                    'internal_notes' => $needsAttention ? 'Please reconfirm pickup location one day before departure.' : null,
                    'assigned_to_name' => $guideTeams[$globalIndex % count($guideTeams)],
                    'tags' => $needsAttention ? ['pickup', 'follow-up'] : ['standard'],
                    'needs_attention' => $needsAttention,
                ]);

                foreach (range(0, 2) as $msgIndex) {
                    ChatMessage::query()->create([
                        'booking_id' => $booking->id,
                        'sender' => $msgIndex % 2 === 0 ? 'customer' : 'operator',
                        'message' => self::ENGLISH_CHAT_MESSAGES[($globalIndex + $msgIndex) % count(self::ENGLISH_CHAT_MESSAGES)],
                        'source' => 'whatsapp',
                        'created_at' => $tourStart->copy()->subDays(2)->addMinutes($msgIndex * 45),
                        'updated_at' => now(),
                    ]);
                }

                if ($status === 'confirmed') {
                    BookingStatusEvent::query()->create([
                        'booking_id' => $booking->id,
                        'old_status' => 'standby',
                        'new_status' => 'confirmed',
                        'changed_by' => 'system',
                        'reason' => 'wa_positive_reply',
                        'source' => 'whatsapp',
                        'source_message_id' => 'seed-msg-'.$booking->id,
                        'metadata' => ['seeded' => true],
                    ]);
                }
            });
        }

        $this->call(TenantCategorySeeder::class);
        $this->call(TenantResourceSeeder::class);
        LandingPricingPlan::syncTenantSeatCapsFromPopularPlan();
    }

    private function resetSeedData(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }
        BookingStatusEvent::query()->delete();
        ChatMessage::query()->delete();
        Booking::query()->delete();
        Customer::query()->delete();
        ChatTemplate::query()->delete();
        User::query()->whereIn('email', [
            'admin@gilitour.test',
            'tenant.east@gilitour.test',
            'guide.east.1@gilitour.test',
            'guide.east.2@gilitour.test',
            'tenant.west@gilitour.test',
            'guide.west.1@gilitour.test',
            'guide.west.2@gilitour.test',
        ])->delete();
        Tenant::query()->whereIn('code', [
            'gilitour-default',
            'gilitour-west',
        ])->delete();
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
