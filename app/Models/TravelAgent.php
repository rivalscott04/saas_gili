<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'signup_url',
        'docs_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenantConnections(): HasMany
    {
        return $this->hasMany(TenantTravelAgentConnection::class);
    }
}
