<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingReschedule extends Model
{
    protected $fillable = [
        'booking_id',
        'requested_by',
        'request_source',
        'workflow_status',
        'old_tour_start_at',
        'requested_tour_start_at',
        'final_tour_start_at',
        'requested_reason',
        'notes',
        'reviewed_by_user_id',
        'reviewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_tour_start_at' => 'datetime',
            'requested_tour_start_at' => 'datetime',
            'final_tour_start_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
