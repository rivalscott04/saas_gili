<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBookingLocalFieldsRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;

class BookingLocalFieldsController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function update(UpdateBookingLocalFieldsRequest $request, Booking $booking): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->canAccessBooking($booking)) {
            return redirect()->route('root');
        }

        $this->bookingService->updateLocalFields($booking, $request->validated(), $viewer);

        return back()->with('system_alert', [
            'icon' => 'success',
            'title' => __('translation.save-booking'),
            'message' => __('translation.booking-updated'),
        ]);
    }
}

