<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingResourceAllocation;
use App\Services\BookingResourceAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingResourceAllocationController extends Controller
{
    public function __construct(private readonly BookingResourceAllocationService $allocationService)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->canAccessBooking($booking) || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $payload = $request->validate([
            'tenant_resource_id' => ['required', 'integer', Rule::exists('tenant_resources', 'id')],
            'allocation_date' => ['required', 'date'],
            'allocated_units' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'allocated_pax' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->allocationService->assign($booking, $payload, (int) $viewer->id);

        return redirect()->route('index', ['any' => 'apps-bookings'])->with('system_alert', [
            'icon' => 'success',
            'title' => 'Resource dialokasikan',
            'message' => 'Alokasi resource untuk booking berhasil disimpan.',
        ]);
    }

    public function destroy(Request $request, Booking $booking, BookingResourceAllocation $allocation): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->canAccessBooking($booking) || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $this->allocationService->unassign($booking, $allocation, (int) $viewer->id);

        return redirect()->route('index', ['any' => 'apps-bookings'])->with('system_alert', [
            'icon' => 'success',
            'title' => 'Alokasi resource dihapus',
            'message' => 'Resource berhasil di-unassign dari booking.',
        ]);
    }
}
