<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingMagicLinkPageController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function show(Request $request, Booking $booking): View
    {
        $booking->loadMissing('customer');
        $token = (string) $request->query('token', '');
        if ($token === '' || ! $this->tokenMatches($booking, $token)) {
            return view('booking-magic-link', [
                'booking' => $booking,
                'state' => 'invalid',
                'token' => '',
                'message' => 'Sorry, this link is invalid or has expired. Please request a new link, and we will be happy to help.',
            ]);
        }

        if ($booking->customer_response !== null) {
            return view('booking-magic-link', [
                'booking' => $booking,
                'state' => 'done',
                'token' => $token,
                'message' => 'Thank you. Your response has already been recorded. We hope you have a great experience.',
            ]);
        }

        if ($booking->status === 'cancelled') {
            return view('booking-magic-link', [
                'booking' => $booking,
                'state' => 'closed',
                'token' => $token,
                'message' => 'This booking is no longer active. If you need help, please contact our team anytime.',
            ]);
        }

        return view('booking-magic-link', [
            'booking' => $booking,
            'state' => 'form',
            'token' => $token,
            'message' => null,
        ]);
    }

    public function submit(Request $request, Booking $booking): RedirectResponse
    {
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:128'],
            'action' => ['required', 'in:confirm,cancel,reschedule'],
        ]);

        if (! $this->tokenMatches($booking, $payload['token'])) {
            return redirect()
                ->route('bookings.magic-link.show', ['booking' => $booking->id, 'token' => $payload['token']])
                ->with('magic_link_alert', [
                    'icon' => 'danger',
                    'message' => 'Sorry, this link is invalid or has expired. Please request a new link, and we will be happy to help.',
                ]);
        }

        if ($booking->customer_response !== null) {
            return redirect()
                ->route('bookings.magic-link.show', ['booking' => $booking->id, 'token' => $payload['token']])
                ->with('magic_link_alert', [
                    'icon' => 'info',
                    'message' => 'Thank you. We have already received your response. We hope you have a great experience.',
                ]);
        }

        if ($booking->status === 'cancelled' && $payload['action'] !== 'cancel') {
            return redirect()
                ->route('bookings.magic-link.show', ['booking' => $booking->id, 'token' => $payload['token']])
                ->with('magic_link_alert', [
                    'icon' => 'warning',
                    'message' => 'This booking is no longer active. If you need help, please contact our team anytime.',
                ]);
        }

        $this->bookingService->respondViaMagicLink($booking, $payload['action']);

        return redirect()
            ->route('bookings.magic-link.show', ['booking' => $booking->id, 'token' => $payload['token']])
            ->with('magic_link_alert', [
                'icon' => 'success',
                'message' => 'Thank you. Your response has been saved. We wish you a great experience.',
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
}
