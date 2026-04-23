<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPricingPlanFeature extends Model
{
    protected $fillable = [
        'landing_pricing_plan_id',
        'display_text',
        'is_included',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LandingPricingPlan::class, 'landing_pricing_plan_id');
    }
}
