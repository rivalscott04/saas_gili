<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'customer_id',
        'tour_id',
        'tour_name',
        'customer_name',
        'customer_email',
        'customer_phone',
        'tour_start_at',
        'location',
        'guide_name',
        'status',
        'booking_source',
        'channel',
        'channel_order_id',
        'external_booking_ref',
        'external_activity_id',
        'external_option_id',
        'external_status',
        'sync_status',
        'last_synced_at',
        'last_sync_error',
        'currency',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'fx_rate_to_idr',
        'revenue_amount',
        'pricing_payload_json',
        'confirmation_token',
        'confirmation_token_hash',
        'confirmation_token_expires_at',
        'confirmed_at',
        'customer_response',
        'customer_responded_at',
        'participants',
        'notes',
        'internal_notes',
        'assigned_to_name',
        'tags',
        'needs_attention',
    ];

    protected function casts(): array
    {
        return [
            'tour_start_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'confirmation_token_expires_at' => 'datetime',
            'customer_responded_at' => 'datetime',
            'tags' => 'array',
            'needs_attention' => 'boolean',
            'gross_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'fx_rate_to_idr' => 'decimal:6',
            'revenue_amount' => 'decimal:2',
            'pricing_payload_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(BookingStatusEvent::class);
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(BookingReschedule::class)->orderByDesc('created_at');
    }

    public function resourceAllocations(): HasMany
    {
        return $this->hasMany(BookingResourceAllocation::class)->orderBy('allocation_date');
    }

    public function latestReschedule(): HasOne
    {
        return $this->hasOne(BookingReschedule::class)->latestOfMany();
    }

    public function scopeVisibleToUser(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $query->where(function (Builder $tenantScope) use ($user): void {
            $tenantScope
                ->where('bookings.tenant_id', $user->tenant_id)
                ->orWhereNull('bookings.tenant_id');
        });

        if ($user->isGuide()) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('bookings.user_id', $user->id)->orWhereNull('bookings.user_id');
            });
        }
    }
}
