<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'timezone',
        'is_active',
        'invoice_logo_path',
        'max_users',
        'landing_pricing_plan_id',
        'billing_cycle',
        'subscription_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_users' => 'integer',
        ];
    }

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(LandingPricingPlan::class, 'landing_pricing_plan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(TenantRole::class);
    }

    public function travelAgentConnections(): HasMany
    {
        return $this->hasMany(TenantTravelAgentConnection::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(TenantResource::class);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'tenant_categories')
            ->withTimestamps();
    }

    public function activeCategories(): BelongsToMany
    {
        return $this->categories()->where('categories.is_active', true);
    }

    /**
     * @return array<int, string>
     */
    public function activeCategoryCodes(): array
    {
        return $this->activeCategories()
            ->pluck('categories.code')
            ->values()
            ->all();
    }
}
