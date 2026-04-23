<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PolicyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMagicLinkResponseRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingMagicLinkController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $booking->loadMissing('customer');
        $token = $request->query('token');
        if (! is_string($token) || $token === '' || ! $this->tokenMatches($booking, $token)) {
            throw PolicyException::forbidden('MAGIC_LINK_INVALID', 'Invalid or missing link.');
        }

        return response()->json([
            'data' => $this->magicLinkPayload($booking),
        ]);
    }

    public function submit(StoreMagicLinkResponseRequest $request, Booking $booking): JsonResponse
    {
        $booking->loadMissing('customer');
        $token = $request->validated('token');
        if (! $this->tokenMatches($booking, $token)) {
            throw PolicyException::forbidden('MAGIC_LINK_INVALID', 'Invalid or missing link.');
        }

        if ($booking->customer_response !== null) {
            return response()->json([
                'message' => 'You have already submitted a response.',
                'data' => $this->magicLinkPayload($booking->refresh()),
            ], 422);
        }

        if ($booking->status === 'cancelled' && $request->validated('action') !== 'cancel') {
            return response()->json([
                'message' => 'This booking is no longer active.',
            ], 422);
        }

        $this->bookingService->respondViaMagicLink($booking, $request->validated('action'));

        return response()->json([
            'message' => 'Thanks — your reply has been saved.',
            'data' => $this->magicLinkPayload($booking->refresh()),
        ]);
    }

    private function tokenMatches(Booking $booking, string $token): bool
    {
        if ($booking->confirmation_token_expires_at?->isPast()) {
            return false;
        }

        if ($booking->confirmation_token_hash) {
            return hash_equals($booking->confirmation_token_hash, hash('sha256', $token));
        }

        if ($booking->confirmation_token !== null) {
            return hash_equals($booking->confirmation_token, $token);
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function magicLinkPayload(Booking $booking): array
    {
        $customerName = $booking->customer?->full_name ?? $booking->customer_name;

        if ($booking->customer_response !== null) {
            return [
                'view' => 'done',
                'tour_name' => $booking->tour_name,
                'customer_name' => $customerName,
                'customer_response' => $booking->customer_response,
                'status' => $booking->status,
            ];
        }

        if ($booking->status === 'cancelled') {
            return [
                'view' => 'closed',
                'tour_name' => $booking->tour_name,
                'customer_name' => $customerName,
                'closed_reason' => 'cancelled',
            ];
        }

        return [
            'view' => 'form',
            'tour_name' => $booking->tour_name,
            'customer_name' => $customerName,
            'tour_start_at' => $booking->tour_start_at?->toISOString(),
            'status' => $booking->status,
        ];
    }
}
