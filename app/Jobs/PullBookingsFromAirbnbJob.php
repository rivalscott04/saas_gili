<?php

namespace App\Jobs;

use App\Services\TravelAgents\AirbnbBookingSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class PullBookingsFromAirbnbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public function __construct(
        public int $tenantId,
        public int $travelAgentId,
        public string $dateFrom,
        public string $dateTo,
        public bool $forceRepull = false,
        public ?int $requestedBy = null,
    ) {
        $this->tries = max(1, (int) config('airbnb.job_tries', 3));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        $configured = config('airbnb.job_backoff');
        if (is_array($configured) && $configured !== []) {
            /** @var list<int> $out */
            $out = array_values(array_map('intval', $configured));

            return $out;
        }

        return [30, 120];
    }

    public function handle(AirbnbBookingSyncService $syncService): void
    {
        $result = $syncService->pullBookings(
            $this->tenantId,
            $this->dateFrom,
            $this->dateTo,
            $this->forceRepull,
            $this->requestedBy
        );

        if ($result['ok'] ?? false) {
            return;
        }

        if ($this->attempts() < $this->tries) {
            throw new RuntimeException((string) ($result['message'] ?? 'Airbnb pull failed'));
        }
    }
}
