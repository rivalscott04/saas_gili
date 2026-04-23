<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRevenueRecapExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_export_recap_csv(): void
    {
        $admin = User::factory()->create(['role' => 'tenant_admin']);
        Booking::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'channel' => 'getyourguide',
            'currency' => 'EUR',
            'net_amount' => 100,
            'fx_rate_to_idr' => 17000,
            'revenue_amount' => 1700000,
            'tour_start_at' => now()->setDate(2026, 4, 20),
        ]);

        $response = $this->actingAs($admin)->get(route('bookings.recap.export', [
            'specific_date' => '2026-04-20',
            'channel' => 'getyourguide',
            'format' => 'csv',
            'delimiter' => 'semicolon',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"No. Booking";"Tanggal Keberangkatan";"Sumber Pesanan"', $csv);
        $this->assertStringContainsString('Pendapatan Bersih (Rupiah)', $csv);
        $this->assertStringContainsString('GetYourGuide', $csv);
    }

    public function test_tenant_admin_can_export_recap_excel_with_colon_delimiter(): void
    {
        $admin = User::factory()->create(['role' => 'tenant_admin']);
        Booking::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'channel' => 'viator',
            'currency' => 'USD',
            'net_amount' => 120,
            'fx_rate_to_idr' => 15800,
            'revenue_amount' => 1896000,
            'tour_start_at' => now()->setDate(2026, 4, 20),
        ]);

        $response = $this->actingAs($admin)->get(route('bookings.recap.export', [
            'specific_date' => '2026-04-20',
            'format' => 'excel',
            'delimiter' => 'colon',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader('content-disposition');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"No. Booking":"Tanggal Keberangkatan":"Sumber Pesanan"', $csv);
        $this->assertStringContainsString('Pendapatan Bersih (Rupiah)', $csv);
        $this->assertStringContainsString('Viator', $csv);
    }
}
