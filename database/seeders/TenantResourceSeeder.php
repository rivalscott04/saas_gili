<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantResource;
use Illuminate\Database\Seeder;

class TenantResourceSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'resource_type' => 'vehicle',
                'name' => 'Bus 01',
                'reference_code' => 'BUS-01',
                'capacity' => 28,
                'status' => 'available',
                'notes' => 'Mainland transfer bus.',
            ],
            [
                'resource_type' => 'vehicle',
                'name' => 'Speedboat 02',
                'reference_code' => 'SB-02',
                'capacity' => 16,
                'status' => 'blocked',
                'block_reason' => 'Scheduled maintenance',
                'blocked_from' => now()->subDay(),
                'blocked_until' => now()->addDays(2),
                'notes' => 'Engine check and hull cleaning.',
            ],
            [
                'resource_type' => 'vehicle',
                'name' => 'Van 03',
                'reference_code' => 'VAN-03',
                'capacity' => 10,
                'status' => 'available',
                'notes' => 'Airport pickup route.',
            ],
            [
                'resource_type' => 'guide_driver',
                'name' => 'Driver Budi',
                'reference_code' => 'DRV-BUDI',
                'capacity' => null,
                'status' => 'available',
                'notes' => 'English speaking.',
            ],
            [
                'resource_type' => 'guide_driver',
                'name' => 'Guide Sari',
                'reference_code' => 'GDE-SARI',
                'capacity' => null,
                'status' => 'available',
                'notes' => 'Snorkeling specialist.',
            ],
            [
                'resource_type' => 'guide_driver',
                'name' => 'Driver Fajar',
                'reference_code' => 'DRV-FAJAR',
                'capacity' => null,
                'status' => 'blocked',
                'block_reason' => 'Annual leave',
                'blocked_from' => now(),
                'blocked_until' => now()->addDays(4),
                'notes' => 'Back next week.',
            ],
            [
                'resource_type' => 'equipment',
                'name' => 'Snorkel Set A',
                'reference_code' => 'EQ-SN-A',
                'capacity' => 20,
                'status' => 'available',
                'notes' => 'Mask + fins package.',
            ],
            [
                'resource_type' => 'equipment',
                'name' => 'Life Jacket Kit',
                'reference_code' => 'EQ-LJ-01',
                'capacity' => 30,
                'status' => 'available',
                'notes' => 'Adult size mixed.',
            ],
            [
                'resource_type' => 'equipment',
                'name' => 'Camping Tent Bundle',
                'reference_code' => 'EQ-TENT-01',
                'capacity' => 8,
                'status' => 'blocked',
                'block_reason' => 'Drying and cleaning',
                'blocked_from' => now()->subHours(6),
                'blocked_until' => now()->addDay(),
                'notes' => 'Post-trip cleaning cycle.',
            ],
        ];

        Tenant::query()->get(['id'])->each(function (Tenant $tenant) use ($templates): void {
            foreach ($templates as $template) {
                TenantResource::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'resource_type' => $template['resource_type'],
                        'name' => $template['name'],
                    ],
                    [
                        'reference_code' => $template['reference_code'] ?? null,
                        'capacity' => $template['capacity'] ?? null,
                        'status' => $template['status'] ?? 'available',
                        'blocked_from' => $template['blocked_from'] ?? null,
                        'blocked_until' => $template['blocked_until'] ?? null,
                        'block_reason' => $template['block_reason'] ?? null,
                        'notes' => $template['notes'] ?? null,
                    ]
                );
            }
        });
    }
}
