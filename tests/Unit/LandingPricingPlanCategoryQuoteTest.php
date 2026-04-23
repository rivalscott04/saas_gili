<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\LandingPricingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LandingPricingPlanCategoryQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_adds_extra_per_category_beyond_slots(): void
    {
        $plan = LandingPricingPlan::query()->create([
            'code' => 'test-plan',
            'name' => 'Test',
            'subtitle' => null,
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_popular' => false,
            'max_users' => 5,
            'icon_class' => 'ri-book-line',
            'sort_order' => 0,
            'category_slots_included' => 1,
            'extra_category_price_monthly' => 10,
            'extra_category_price_yearly' => 100,
        ]);

        $this->assertSame(120, $plan->quoteForCategoryCount(3, false));
        $this->assertSame(1200, $plan->quoteForCategoryCount(3, true));
    }

    public function test_platinum_style_two_slots_included_before_extra(): void
    {
        $plan = LandingPricingPlan::query()->create([
            'code' => 'test-plan-2',
            'name' => 'Test 2',
            'subtitle' => null,
            'price_monthly' => 50,
            'price_yearly' => 500,
            'is_popular' => false,
            'max_users' => 5,
            'icon_class' => 'ri-book-line',
            'sort_order' => 0,
            'category_slots_included' => 2,
            'extra_category_price_monthly' => 15,
            'extra_category_price_yearly' => 150,
        ]);

        $this->assertSame(50, $plan->quoteForCategoryCount(2, false));
        $this->assertSame(65, $plan->quoteForCategoryCount(3, false));
    }

    public function test_quote_for_category_ids_rejects_disallowed_category(): void
    {
        $a = Category::query()->create(['code' => 'cat_a', 'name' => 'A', 'is_active' => true]);
        $b = Category::query()->create(['code' => 'cat_b', 'name' => 'B', 'is_active' => true]);
        $plan = LandingPricingPlan::query()->create([
            'code' => 'restricted',
            'name' => 'R',
            'subtitle' => null,
            'price_monthly' => 10,
            'price_yearly' => 100,
            'is_popular' => false,
            'max_users' => 2,
            'icon_class' => 'ri-book-line',
            'sort_order' => 0,
            'category_slots_included' => 1,
            'extra_category_price_monthly' => 1,
            'extra_category_price_yearly' => 2,
        ]);
        $plan->allowedCategories()->sync([(int) $a->id]);

        $plan->load('allowedCategories');
        $this->assertSame(10, $plan->quoteForCategoryIds([(int) $a->id], false));

        $this->expectException(ValidationException::class);
        $plan->quoteForCategoryIds([(int) $b->id], false);
    }
}
