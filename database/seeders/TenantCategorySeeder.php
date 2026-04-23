<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['code' => 'tour_operator', 'name' => 'Tour Operator'],
            ['code' => 'vehicle_rental', 'name' => 'Vehicle Rental'],
            ['code' => 'travel_agency', 'name' => 'Travel Agency'],
            ['code' => 'activity_provider', 'name' => 'Activity Provider'],
        ];

        foreach ($defaults as $category) {
            Category::query()->updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );
        }

        $defaultCategoryId = (int) Category::query()
            ->where('code', 'tour_operator')
            ->value('id');

        if ($defaultCategoryId <= 0) {
            return;
        }

        Tenant::query()
            ->withCount('categories')
            ->having('categories_count', '=', 0)
            ->get()
            ->each(function (Tenant $tenant) use ($defaultCategoryId): void {
                $tenant->categories()->syncWithoutDetaching([$defaultCategoryId]);
            });
    }
}
