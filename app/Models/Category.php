<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_categories')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<LandingPricingPlan, $this>
     */
    public function landingPricingPlans(): BelongsToMany
    {
        return $this->belongsToMany(
            LandingPricingPlan::class,
            'landing_pricing_plan_allowed_categories',
            'category_id',
            'landing_pricing_plan_id'
        )->withTimestamps();
    }
}
