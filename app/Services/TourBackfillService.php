<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Support\Collection;

class TourBackfillService
{
    private const PAIR_LOOKUP_CHUNK = 100;

    public function backfillFromBookingSnapshots(): int
    {
        $updatedBookings = 0;

        Booking::query()
            ->whereNotNull('tenant_id')
            ->whereNotNull('tour_name')
            ->where('tour_name', '!=', '')
            ->select('tenant_id', 'tour_name')
            ->distinct()
            ->orderBy('tenant_id')
            ->chunk(500, function ($pairs) use (&$updatedBookings): void {
                $list = Collection::make($pairs)
                    ->map(fn ($p): array => [
                        'tenant_id' => (int) $p->tenant_id,
                        'tour_name' => trim((string) $p->tour_name),
                    ])
                    ->filter(fn (array $row): bool => $row['tenant_id'] > 0 && $row['tour_name'] !== '')
                    ->unique(fn (array $row): string => $row['tenant_id'].'|'.$row['tour_name'])
                    ->values();

                if ($list->isEmpty()) {
                    return;
                }

                $tourIdsByPairKey = $this->loadTourIdsByPairKeys($list);

                foreach ($list as $pair) {
                    $key = $pair['tenant_id'].'|'.$pair['tour_name'];
                    $tourId = $tourIdsByPairKey->get($key);

                    if ($tourId === null) {
                        $tour = Tour::query()->create([
                            'tenant_id' => $pair['tenant_id'],
                            'name' => $pair['tour_name'],
                            'is_active' => true,
                            'sort_order' => 0,
                        ]);
                        $tourId = (int) $tour->id;
                        $tourIdsByPairKey->put($key, $tourId);
                    }

                    $updatedBookings += Booking::query()
                        ->where('tenant_id', $pair['tenant_id'])
                        ->where('tour_name', $pair['tour_name'])
                        ->whereNull('tour_id')
                        ->update(['tour_id' => $tourId]);
                }
            });

        return $updatedBookings;
    }

    /**
     * @param  Collection<int, array{tenant_id: int, tour_name: string}>  $pairs
     * @return Collection<string, int>
     */
    private function loadTourIdsByPairKeys(Collection $pairs): Collection
    {
        $byKey = collect();

        foreach ($pairs->chunk(self::PAIR_LOOKUP_CHUNK) as $chunk) {
            $query = Tour::query()->select(['id', 'tenant_id', 'name']);

            $query->where(function ($q) use ($chunk): void {
                foreach ($chunk as $pair) {
                    $q->orWhere(function ($nested) use ($pair): void {
                        $nested->where('tenant_id', $pair['tenant_id'])
                            ->where('name', $pair['tour_name']);
                    });
                }
            });

            foreach ($query->get() as $tour) {
                $byKey->put((int) $tour->tenant_id.'|'.$tour->name, (int) $tour->id);
            }
        }

        return $byKey;
    }
}
