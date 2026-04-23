<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourResourceRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tour_id',
        'resource_type',
        'is_required',
        'min_units',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'min_units' => 'integer',
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
}
