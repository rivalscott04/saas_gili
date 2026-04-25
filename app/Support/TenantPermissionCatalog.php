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
        'travel_agents.view' => 'Lihat integrasi travel agent',
        'travel_agents.manage_connection' => 'Sambungkan / putus koneksi travel agent',
        'travel_agents.test_connection' => 'Uji koneksi travel agent',
        'travel_agents.sync' => 'Sinkronisasi booking ke channel',
        'travel_agents.view_logs' => 'Lihat log sinkronisasi channel',
        'travel_agents.retry_failed_jobs' => 'Coba ulang job sinkronisasi gagal',
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
            'travel_agents.view',
            'travel_agents.manage_connection',
            'travel_agents.test_connection',
            'travel_agents.sync',
            'travel_agents.view_logs',
            'travel_agents.retry_failed_jobs',
        ],
        'operator' => [
            'bookings.view',
            'bookings.send_reminder',
            'bookings.manage_reschedule',
            'travel_agents.view',
            'travel_agents.manage_connection',
            'travel_agents.test_connection',
            'travel_agents.sync',
            'travel_agents.view_logs',
            'travel_agents.retry_failed_jobs',
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
