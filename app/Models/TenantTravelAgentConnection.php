<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTravelAgentConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'travel_agent_id',
        'status',
        'api_key',
        'api_secret',
        'account_reference',
        'extra_config',
        'connected_at',
        'last_checked_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'extra_config' => 'array',
            'connected_at' => 'datetime',
            'last_checked_at' => 'datetime',
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
