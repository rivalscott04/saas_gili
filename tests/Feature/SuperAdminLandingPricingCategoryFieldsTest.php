<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPricingPlan;
use App\Models\User;
use Database\Seeders\LandingPricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminLandingPricingCategoryFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_plan_category_pricing_and_allowed_categories(): void
    {
        $this->seed(LandingPricingSeeder::class);
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);
        $plan = LandingPricingPlan::query()->where('code', 'basic')->firstOrFail();
        $catTour = Category::query()->where('code', 'tour_operator')->firstOrFail();

        $featuresPayload = $plan->features->values()->map(
            fn ($feature) => [
                'display_text' => $feature->display_text,
                'is_included' => $feature->is_included ? '1' : '0',
            ]
        )->all();

        $this->actingAs($superadmin)->post(
            route('superadmin-landing-pricing.update', $plan),
            [
                'plans' => [
                    $plan->id => [
                        'name' => $plan->name,
                        'subtitle' => $plan->subtitle,
                        'price_monthly' => $plan->price_monthly,
                        'price_yearly' => $plan->price_yearly,
                        'is_popular' => '0',
                        'max_users_unlimited' => '0',
                        'max_users' => (int) $plan->max_users,
                        'icon_class' => $plan->icon_class,
                        'sort_order' => (int) $plan->sort_order,
                        'category_slots_included' => 1,
                        'extra_category_price_monthly' => 9,
                        'extra_category_price_yearly' => 80,
                        'allowed_category_ids' => [(int) $catTour->id],
                        'features' => $featuresPayload,
                    ],
                ],
            ]
        )->assertRedirect(route('superadmin-landing-pricing.index'));

        $plan->refresh();
        $this->assertSame(9, (int) $plan->extra_category_price_monthly);
        $this->assertSame(80, (int) $plan->extra_category_price_yearly);
        $this->assertTrue($plan->allowedCategories()->whereKey($catTour->id)->exists());
        $this->assertFalse($plan->allowedCategories()->where('code', 'vehicle_rental')->exists());
    }
}
