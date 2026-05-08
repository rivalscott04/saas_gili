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
            // Nama kategori tampil di UI (bahasa Indonesia).
            ['code' => 'tour_operator', 'name' => 'Operator Tur'],
            ['code' => 'vehicle_rental', 'name' => 'Sewa Kendaraan'],
            ['code' => 'travel_agency', 'name' => 'Agen Travel'],
            ['code' => 'activity_provider', 'name' => 'Penyedia Aktivitas'],
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
                'name' => 'Paket Basic',
                'subtitle' => 'Untuk mulai operasional',
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
                    ['Maks. <b>3</b> user', true],
                    ['Maks. <b>300</b> pelanggan', true],
                    ['Manajemen booking', true],
                    ['Kapasitas harian dasar', true],
                    ['Integrasi channel (opsional)', false],
                    ['Dukungan prioritas', false],
                ],
            ],
            [
                'code' => 'pro',
                'name' => 'Paket Pro',
                'subtitle' => 'Untuk tim yang bertumbuh',
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
                    ['Maks. <b>15</b> user', true],
                    ['Pelanggan tanpa batas', true],
                    ['Kapasitas harian + override per tanggal', true],
                    ['Template pesan / catatan operasional', true],
                    ['Integrasi channel (GetYourGuide, dll)', true],
                    ['Dukungan prioritas', true],
                ],
            ],
            [
                'code' => 'platinum',
                'name' => 'Paket Platinum',
                'subtitle' => 'Untuk kebutuhan enterprise',
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
                    ['User tanpa batas', true],
                    ['Pelanggan tanpa batas', true],
                    ['Multi-kategori bisnis (lebih fleksibel)', true],
                    ['Integrasi channel prioritas', true],
                    ['SLA dukungan & onboarding', true],
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
