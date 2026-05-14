<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantOnboardingState extends Model
{
    use HasFactory;

    public const MODE_TWO_WAY_SYNC = 'two_way_sync';

    public const MODE_APP_ONLY = 'app_only';

    /**
     * @var array<int, string>
     */
    public const ALLOWED_MODES = [
        self::MODE_TWO_WAY_SYNC,
        self::MODE_APP_ONLY,
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'mode',
        'dismissed_at',
        'step_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
            'step_completed_at' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
