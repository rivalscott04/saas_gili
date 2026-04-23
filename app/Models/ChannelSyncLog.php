<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'travel_agent_id',
        'event_type',
        'direction',
        'status',
        'message',
        'payload',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function travelAgent(): BelongsTo
    {
        return $this->belongsTo(TravelAgent::class);
    }
}
