<?php

namespace Tests\Feature;

use App\Models\LandingPricingPlan;
use Database\Seeders\LandingPricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LandingPricingQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_with_features_uses_three_queries_and_no_lazy_load_when_touching_features(): void
    {
        $this->seed(LandingPricingSeeder::class);

        DB::enableQueryLog();

        $plans = LandingPricingPlan::allWithFeaturesForDisplay();
        foreach ($plans as $plan) {
            foreach ($plan->features as $feature) {
                $this->assertNotSame('', $feature->display_text);
            }
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(3, $queries, 'Expected 3 queries (plans + features + allowedCategories eager load), got '.count($queries));
    }

    public function test_root_page_loads_without_lazy_loading_violation(): void
    {
        $this->seed(LandingPricingSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Basic Plan', false);
    }

    public function test_platinum_plan_preserves_null_max_users_after_round_trip(): void
    {
        $this->seed(LandingPricingSeeder::class);

        $platinum = LandingPricingPlan::query()->where('code', 'platinum')->first();
        $this->assertNotNull($platinum);
        $this->assertNull($platinum->max_users);

        $loaded = LandingPricingPlan::allWithFeaturesForDisplay()->firstWhere('code', 'platinum');
        $this->assertNotNull($loaded);
        $this->assertNull($loaded->max_users);
    }
}
