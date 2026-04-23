<?php

namespace App\Support;

class AccessAlert
{
    public const REASON_SEAT_LIMIT_REACHED = 'SEAT_LIMIT_REACHED';
    public const REASON_SUBSCRIPTION_EXPIRED = 'SUBSCRIPTION_EXPIRED';
    public const REASON_USER_SUSPENDED = 'USER_SUSPENDED';

    /**
     * @return array<string, string>
     */
    public static function fromReason(string $reason): array
    {
        return match ($reason) {
            self::REASON_SEAT_LIMIT_REACHED => [
                'reason' => self::REASON_SEAT_LIMIT_REACHED,
                'icon' => 'warning',
                'title' => 'Seat limit reached',
                'message' => 'Kapasitas user aktif pada paket saat ini sudah penuh.',
            ],
            self::REASON_SUBSCRIPTION_EXPIRED => [
                'reason' => self::REASON_SUBSCRIPTION_EXPIRED,
                'icon' => 'error',
                'title' => 'Subscription expired',
                'message' => 'Akses ditolak karena subscription tenant tidak aktif.',
            ],
            self::REASON_USER_SUSPENDED => [
                'reason' => self::REASON_USER_SUSPENDED,
                'icon' => 'error',
                'title' => 'User suspended',
                'message' => 'Akun Anda sedang dinonaktifkan. Hubungi main user/admin tenant.',
            ],
            default => [
                'reason' => 'ACCESS_DENIED',
                'icon' => 'error',
                'title' => 'Access denied',
                'message' => 'Akses Anda ditolak oleh kebijakan sistem.',
            ],
        };
    }
}
