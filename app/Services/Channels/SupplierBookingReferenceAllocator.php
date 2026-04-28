<?php

namespace App\Services\Channels;

use App\Models\Booking;
use Illuminate\Database\QueryException;

/**
 * Allocates a stable, partner-safe booking identifier (short alphanumeric)
 * for use across OTAs / supplier APIs (e.g. GetYourGuide bookingReference cap).
 */
class SupplierBookingReferenceAllocator
{
    public function ensureFor(Booking $booking): string
    {
        $existing = trim((string) ($booking->supplier_booking_reference ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $maxLen = max(8, min(64, (int) config('channels.supplier_booking_reference.max_length', 25)));
        $byteLen = max(4, min(16, (int) config('channels.supplier_booking_reference.random_bytes', 8)));

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $candidate = strtoupper(bin2hex(random_bytes($byteLen)));
            if (strlen($candidate) > $maxLen) {
                $candidate = substr($candidate, 0, $maxLen);
            }

            try {
                $updated = Booking::query()
                    ->whereKey($booking->getKey())
                    ->whereNull('supplier_booking_reference')
                    ->update(['supplier_booking_reference' => $candidate]);

                if ($updated === 1) {
                    return $candidate;
                }

                $booking->refresh();

                return trim((string) ($booking->supplier_booking_reference ?? '')) ?: $candidate;
            } catch (QueryException) {
                continue;
            }
        }

        throw new \RuntimeException('Unable to allocate a unique supplier_booking_reference.');
    }

    /**
     * Optional human/OTA reference for partner payloads when it already fits
     * partner constraints; otherwise callers should omit or use ensureFor().
     */
    public function sanitizeChannelOrderId(?string $channelOrderId, int $maxLength): ?string
    {
        $raw = trim((string) ($channelOrderId ?? ''));
        if ($raw === '') {
            return null;
        }

        if (strlen($raw) > $maxLength) {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $raw)) {
            return null;
        }

        return $raw;
    }
}
