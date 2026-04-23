<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantResource extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'resource_type',
        'name',
        'reference_code',
        'capacity',
        'status',
        'blocked_from',
        'blocked_until',
        'block_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'blocked_from' => 'datetime',
            'blocked_until' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bookingAllocations(): HasMany
    {
        return $this->hasMany(BookingResourceAllocation::class, 'tenant_resource_id');
    }
}
