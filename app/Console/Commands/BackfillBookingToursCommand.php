<?php

namespace App\Console\Commands;

use App\Services\TourBackfillService;
use Illuminate\Console\Command;

class BackfillBookingToursCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bookings:backfill-tour-links';

    /**
     * @var string
     */
    protected $description = 'Backfill bookings.tour_id from bookings.tour_name snapshots';

    public function handle(TourBackfillService $backfillService): int
    {
        $count = $backfillService->backfillFromBookingSnapshots();
        $this->info("Backfill selesai. Booking ter-update: {$count}");

        return self::SUCCESS;
    }
}
