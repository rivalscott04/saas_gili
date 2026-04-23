<?php

namespace App\Models;

use App\Services\TenantAuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class TourDayCapacity extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'tour_id',
        'service_date',
        'max_pax',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'max_pax' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    protected static function booted(): void
    {
        static::created(function (TourDayCapacity $capacity): void {
            app(TenantAuditLogService::class)->record(
                tenantId: (int) $capacity->tenant_id,
                eventType: 'capacity.created',
                actorUserId: Auth::id(),
                entityType: 'tour_day_capacity',
                entityId: (int) $capacity->id,
                tourId: (int) $capacity->tour_id,
                serviceDate: $capacity->service_date,
                context: ['max_pax' => $capacity->max_pax]
            );
        });

        static::updated(function (TourDayCapacity $capacity): void {
            app(TenantAuditLogService::class)->record(
                tenantId: (int) $capacity->tenant_id,
                eventType: 'capacity.updated',
                actorUserId: Auth::id(),
                entityType: 'tour_day_capacity',
                entityId: (int) $capacity->id,
                tourId: (int) $capacity->tour_id,
                serviceDate: $capacity->service_date,
                context: [
                    'before' => $capacity->getOriginal(),
                    'after' => $capacity->getAttributes(),
                ]
            );
        });

        static::deleted(function (TourDayCapacity $capacity): void {
            app(TenantAuditLogService::class)->record(
                tenantId: (int) $capacity->tenant_id,
                eventType: 'capacity.deleted',
                actorUserId: Auth::id(),
                entityType: 'tour_day_capacity',
                entityId: (int) $capacity->id,
                tourId: (int) $capacity->tour_id,
                serviceDate: $capacity->service_date,
                context: ['max_pax' => $capacity->max_pax]
            );
        });
    }
}
