<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Support\TenantPermissionCatalog;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'subscription_status',
        'seat_limit_reached',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'seat_limit_reached' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return strtolower((string) $this->role) === 'superadmin';
    }

    public function isTenantAdmin(): bool
    {
        return strtolower((string) $this->role) === 'tenant_admin' && $this->tenant_id !== null;
    }

    public function isGuide(): bool
    {
        return ! ($this->isSuperAdmin() || $this->isTenantAdmin());
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantAdmin();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuspended(): bool
    {
        return strtolower((string) ($this->status ?? 'active')) === 'suspended';
    }

    public function hasExpiredSubscription(): bool
    {
        $status = strtolower((string) ($this->subscription_status ?? 'active'));

        return in_array($status, ['expired', 'cancelled'], true);
    }

    public function isSeatLimitReached(): bool
    {
        return (bool) $this->seat_limit_reached;
    }

    public function bookingsVisibleQuery(): Builder
    {
        if ($this->isSuperAdmin()) {
            return Booking::query();
        }

        return Booking::query()->visibleToUser($this);
    }

    public function canAccessBooking(Booking $booking): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($booking->tenant_id !== null && (int) $booking->tenant_id !== (int) $this->tenant_id) {
            return false;
        }

        if ($this->isTenantAdmin()) {
            return true;
        }

        return $booking->user_id === null || (int) $booking->user_id === (int) $this->id;
    }

    public function hasTenantPermission(string $permissionKey): bool
    {
        if ($this->isSuperAdmin() || $this->isTenantAdmin()) {
            return true;
        }

        $role = strtolower((string) $this->role);
        $allowedDefaults = TenantPermissionCatalog::defaultsForRole($role);
        $defaultAllowed = in_array($permissionKey, $allowedDefaults, true);

        if (! $this->tenant_id || $role === '') {
            return $defaultAllowed;
        }

        $override = DB::table('tenant_role_permissions')
            ->where('tenant_id', $this->tenant_id)
            ->where('role', $role)
            ->where('permission_key', $permissionKey)
            ->value('is_allowed');

        if ($override === null) {
            return $defaultAllowed;
        }

        return (bool) $override;
    }

    public function hasPlatformPermission(string $permissionKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return false;
    }
}
