<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\TravelAgents\GetYourGuideBookingSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class PushBookingToGetYourGuideJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public function __construct(
        public int $bookingId,
        public int $tenantId,
    ) {
        $this->tries = max(1, (int) config('services.getyourguide.job_tries', 5));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        $configured = config('services.getyourguide.job_backoff');
        if (is_array($configured) && $configured !== []) {
            /** @var list<int> $out */
            $out = array_values(array_map('intval', $configured));

            return $out;
        }

        return [15, 60, 300];
    }

    public function handle(GetYourGuideBookingSyncService $syncService): void
    {
        $booking = Booking::query()->find($this->bookingId);
        if (! $booking || (int) $booking->tenant_id !== $this->tenantId) {
            return;
        }

        $result = $syncService->syncCreateBooking($booking, $this->tenantId);
        if ($result['ok'] ?? false) {
            return;
        }

        $retryable = (bool) data_get($result, 'error.retryable', false);
        if ($retryable && $this->attempts() < $this->tries) {
            throw new RuntimeException((string) data_get($result, 'error.message', 'GetYourGuide sync failed'));
        }
    }
}
