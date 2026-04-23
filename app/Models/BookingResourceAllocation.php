<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingResourceAllocation extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'booking_id',
        'tenant_resource_id',
        'allocation_date',
        'allocated_units',
        'allocated_pax',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'allocation_date' => 'date',
            'allocated_units' => 'integer',
            'allocated_pax' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(TenantResource::class, 'tenant_resource_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
