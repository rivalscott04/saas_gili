<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LandingPricingPlan;
use App\Models\LandingPricingPlanFeature;
use Illuminate\Database\Seeder;

class LandingPricingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'tour_operator', 'name' => 'Tour Operator'],
            ['code' => 'vehicle_rental', 'name' => 'Vehicle Rental'],
            ['code' => 'travel_agency', 'name' => 'Travel Agency'],
            ['code' => 'activity_provider', 'name' => 'Activity Provider'],
        ] as $categoryRow) {
            Category::query()->updateOrCreate(
                ['code' => $categoryRow['code']],
                ['name' => $categoryRow['name'], 'is_active' => true]
            );
        }
        $categoryIdsByCode = Category::query()
            ->whereIn('code', ['tour_operator', 'vehicle_rental', 'travel_agency', 'activity_provider'])
            ->pluck('id', 'code');

        $plans = [
            [
                'code' => 'basic',
                'name' => 'Basic Plan',
                'subtitle' => 'For Startup',
                'price_monthly' => 19,
                'price_yearly' => 171,
                'is_popular' => false,
                'max_users' => 3,
                'icon_class' => 'ri-book-line',
                'sort_order' => 0,
                'category_slots_included' => 1,
                'extra_category_price_monthly' => 5,
                'extra_category_price_yearly' => 45,
                'allowed_category_codes' => ['tour_operator'],
                'features' => [
                    ['Upto 3 Projects', true],
                    ['Upto 299 Customers', true],
                    ['Scalable Bandwidth', true],
                    ['5 FTP Login', true],
                    ['24/7 Support', false],
                    ['Unlimited Storage', false],
                    ['Domain', false],
                ],
            ],
            [
                'code' => 'pro',
                'name' => 'Pro Business',
                'subtitle' => 'Professional plans',
                'price_monthly' => 29,
                'price_yearly' => 261,
                'is_popular' => true,
                'max_users' => 15,
                'icon_class' => 'ri-medal-fill',
                'sort_order' => 1,
                'category_slots_included' => 1,
                'extra_category_price_monthly' => 8,
                'extra_category_price_yearly' => 72,
                'allowed_category_codes' => ['tour_operator', 'vehicle_rental', 'travel_agency'],
                'features' => [
                    ['Upto 15 Projects', true],
                    ['Unlimited Customers', true],
                    ['Scalable Bandwidth', true],
                    ['12 FTP Login', true],
                    ['24/7 Support', true],
                    ['Unlimited Storage', false],
                    ['Domain', false],
                ],
            ],
            [
                'code' => 'platinum',
                'name' => 'Platinum Plan',
                'subtitle' => 'Enterprise Businesses',
                'price_monthly' => 39,
                'price_yearly' => 351,
                'is_popular' => false,
                'max_users' => null,
                'icon_class' => 'ri-stack-fill',
                'sort_order' => 2,
                'category_slots_included' => 2,
                'extra_category_price_monthly' => 12,
                'extra_category_price_yearly' => 100,
                'allowed_category_codes' => ['tour_operator', 'vehicle_rental', 'travel_agency', 'activity_provider'],
                'features' => [
                    ['Unlimited Projects', true],
                    ['Unlimited Customers', true],
                    ['Scalable Bandwidth', true],
                    ['Unlimited FTP Login', true],
                    ['24/7 Support', true],
                    ['Unlimited Storage', true],
                    ['Domain', true],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            $allowedCategoryCodes = $planData['allowed_category_codes'] ?? [];
            unset($planData['features'], $planData['allowed_category_codes']);

            $plan = LandingPricingPlan::query()->updateOrCreate(
                ['code' => $planData['code']],
                $planData
            );

            $plan->features()->delete();
            foreach ($features as $index => [$text, $included]) {
                LandingPricingPlanFeature::query()->create([
                    'landing_pricing_plan_id' => $plan->id,
                    'display_text' => $text,
                    'is_included' => $included,
                    'sort_order' => $index,
                ]);
            }

            $allowedCategoryIds = collect($allowedCategoryCodes)
                ->map(static fn (string $code): int => (int) ($categoryIdsByCode[$code] ?? 0))
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();
            $plan->allowedCategories()->sync($allowedCategoryIds);
        }

        LandingPricingPlan::syncTenantSeatCapsFromPopularPlan();
    }
}
