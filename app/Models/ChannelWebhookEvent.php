<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelWebhookEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'travel_agent_id',
        'dedupe_key',
        'event_kind',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
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
