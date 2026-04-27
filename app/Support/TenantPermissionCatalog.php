<?php

namespace App\Support;

class TenantPermissionCatalog
{
    /**
     * @var array<string, string>
     */
    public const LABELS = [
        'bookings.view' => 'Akses halaman Booking',
        'bookings.send_reminder' => 'Kirim reminder WhatsApp',
        'bookings.manage_reschedule' => 'Kelola workflow reschedule',
        'invoices.view' => 'Lihat menu Invoice',
        'whatsapp_templates.manage' => 'Kelola WA Template',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const DEFAULTS_BY_ROLE = [
        'tenant_admin' => [
            'bookings.view',
            'bookings.send_reminder',
            'bookings.manage_reschedule',
            'invoices.view',
            'whatsapp_templates.manage',
        ],
        'operator' => [
            'bookings.view',
            'bookings.send_reminder',
            'bookings.manage_reschedule',
        ],
        'guide' => [
            'bookings.view',
            'bookings.send_reminder',
            'bookings.manage_reschedule',
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function defaultsForRole(string $role): array
    {
        return self::DEFAULTS_BY_ROLE[strtolower($role)] ?? [];
    }
}
