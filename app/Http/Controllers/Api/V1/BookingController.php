<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBookingLocalFieldsRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = $this->bookingService->paginate($request->all(), $request->user());

        return BookingResource::collection($bookings);
    }

    public function assignees(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $q = $request->query('q');
        $names = $this->bookingService->assigneeNameSuggestions(
            $request->user(),
            is_string($q) ? $q : null,
        );

        return response()->json(['data' => $names]);
    }

    public function show(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->loadMissing('customer'));
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): BookingResource
    {
        $this->authorize('update', $booking);

        $updatedBooking = $this->bookingService->updateStatus($booking, $request->validated('status'));

        return new BookingResource($updatedBooking->loadMissing('customer'));
    }

    public function updateLocalFields(UpdateBookingLocalFieldsRequest $request, Booking $booking): BookingResource
    {
        $this->authorize('update', $booking);

        $updatedBooking = $this->bookingService->updateLocalFields($booking, $request->validated(), $request->user());

        return new BookingResource($updatedBooking->loadMissing('customer'));
    }

    public function issueConfirmationLink(Booking $booking)
    {
        $this->authorize('update', $booking);

        [$booking, $plainToken] = $this->bookingService->generateConfirmationToken($booking);
        $base = config('app.frontend_url');
        $url = $base.'/booking/'.$booking->id.'/respond?'.http_build_query([
            'token' => $plainToken,
        ]);

        return response()->json([
            'data' => [
                'booking_id' => $booking->id,
                'confirm_url' => $url,
            ],
        ]);
    }
}
