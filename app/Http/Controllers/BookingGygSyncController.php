<?php

namespace App\Http\Controllers;

use App\Jobs\PushBookingToGetYourGuideJob;
use App\Models\Booking;
use App\Services\TravelAgents\GetYourGuideBookingSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingGygSyncController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->canAccessBooking($booking)) {
            return redirect()->route('root');
        }

        $tenantId = (int) $booking->tenant_id;
        if ($tenantId <= 0) {
            return redirect()->route('root');
        }

        $isRetry = strtolower((string) ($booking->sync_status ?? '')) === 'error';
        if ($isRetry) {
            if (! $viewer->hasTenantPermission('travel_agents.retry_failed_jobs')) {
                return back()->with('system_alert', [
                    'icon' => 'warning',
                    'title' => 'Akses ditolak',
                    'message' => 'Anda tidak punya izin untuk mencoba ulang sinkronisasi yang gagal.',
                ]);
            }
        } elseif (! $viewer->hasTenantPermission('travel_agents.sync')) {
            return back()->with('system_alert', [
                'icon' => 'warning',
                'title' => 'Akses ditolak',
                'message' => 'Anda tidak punya izin sinkronisasi ke channel.',
            ]);
        }

        if (trim((string) ($booking->external_activity_id ?? '')) === '') {
            return back()->with('system_alert', [
                'icon' => 'warning',
                'title' => 'GetYourGuide',
                'message' => __('translation.gyg-sync-missing-activity'),
            ]);
        }

        if (config('services.getyourguide.sync_via_queue', true)) {
            PushBookingToGetYourGuideJob::dispatch($booking->id, $tenantId);
            $message = __('translation.gyg-sync-queued');
        } else {
            app(GetYourGuideBookingSyncService::class)->syncCreateBooking($booking->fresh(), $tenantId);
            $message = __('translation.gyg-sync-inline-done');
        }

        return back()->with('system_alert', [
            'icon' => 'success',
            'title' => 'GetYourGuide',
            'message' => $message,
        ]);
    }
}
