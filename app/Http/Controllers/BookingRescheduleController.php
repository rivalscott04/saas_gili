<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingRescheduleController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function updateWorkflow(Request $request, Booking $booking): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->canAccessBooking($booking) || ! $viewer->hasTenantPermission('bookings.manage_reschedule')) {
            return redirect()->route('root');
        }

        $payload = $request->validate([
            'reschedule_id' => [
                'required',
                'integer',
                Rule::exists('booking_reschedules', 'id')->where(
                    fn ($query) => $query->where('booking_id', $booking->id)
                ),
            ],
            'workflow_status' => ['required', Rule::in(['reviewed', 'approved', 'rejected', 'completed'])],
            'requested_tour_start_at' => ['nullable', 'date'],
            'final_tour_start_at' => ['nullable', 'date'],
            'requested_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var BookingReschedule $reschedule */
        $reschedule = BookingReschedule::query()->findOrFail((int) $payload['reschedule_id']);

        if ($payload['workflow_status'] === 'completed' && empty($payload['final_tour_start_at'])) {
            return redirect()
                ->route('index', ['any' => 'apps-bookings'])
                ->with('system_alert', [
                    'reason' => 'RESCHEDULE_FINAL_DATE_REQUIRED',
                    'icon' => 'warning',
                    'title' => 'Tanggal final wajib diisi',
                    'message' => 'Isi jadwal final keberangkatan untuk menyelesaikan reschedule.',
                ]);
        }

        $this->bookingService->updateRescheduleWorkflow($booking, $reschedule, $payload, (int) $viewer->id);

        return redirect()
            ->route('index', ['any' => 'apps-bookings'])
            ->with('system_alert', [
                'reason' => 'RESCHEDULE_WORKFLOW_UPDATED',
                'icon' => 'success',
                'title' => 'Reschedule diperbarui',
                'message' => 'Status workflow reschedule berhasil disimpan.',
            ]);
    }
}
