<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tour extends Model
{
    use HasFactory;

    public const ALLOCATION_NONE = 'none';

    public const ALLOCATION_SNORKELING = 'snorkeling';

    public const ALLOCATION_LAND_ACTIVITY = 'land_activity';

    /**
     * @var array<string, string>
     */
    public const RESOURCE_TYPE_LABELS = [
        'vehicle' => 'Vehicle',
        'guide_driver' => 'Guide/Driver',
        'equipment' => 'Equipment',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'default_max_pax_per_day',
        'is_active',
        'sort_order',
        'allocation_requirement',
    ];

    protected function casts(): array
    {
        return [
            'default_max_pax_per_day' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function dayCapacities(): HasMany
    {
        return $this->hasMany(TourDayCapacity::class);
    }

    public function resourceRequirements(): HasMany
    {
        return $this->hasMany(TourResourceRequirement::class)
            ->orderBy('resource_type');
    }
}
