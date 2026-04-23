<?php

namespace Tests\Feature;

use Database\Seeders\LandingPricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $this->seed(LandingPricingSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Plans', false);
    }
}
