<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingListHistoryService
{
    /**
     * @param  Collection<int, int|string>  $bookingIds
     * @return array<int, list<array{sent_at: ?string, template_name: string, sent_to_phone: string}>>
     */
    public function reminderHistoryByBooking(Collection $bookingIds, int $perBooking = 5): array
    {
        if ($bookingIds->isEmpty()) {
            return [];
        }

        $rows = $this->rankedRows(
            'booking_status_events',
            $bookingIds,
            ['reason' => 'reminder_sent'],
            $perBooking,
            ['booking_id', 'created_at', 'metadata']
        );

        return $this->groupReminderRows($rows);
    }

    /**
     * @param  Collection<int, int|string>  $bookingIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function rescheduleHistoryByBooking(Collection $bookingIds, int $perBooking = 8): array
    {
        if ($bookingIds->isEmpty()) {
            return [];
        }

        $rows = $this->rankedRows(
            'booking_reschedules',
            $bookingIds,
            [],
            $perBooking,
            [
                'id',
                'booking_id',
                'requested_by',
                'request_source',
                'workflow_status',
                'old_tour_start_at',
                'requested_tour_start_at',
                'final_tour_start_at',
                'requested_reason',
                'notes',
                'reviewed_at',
                'completed_at',
                'created_at',
            ]
        );

        return $this->groupRescheduleRows($rows);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $columns
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rankedRows(
        string $table,
        Collection $bookingIds,
        array $filters,
        int $perBooking,
        array $columns,
    ): Collection {
        $columnList = implode(', ', array_map(
            fn (string $column): string => $table.'.'.$column,
            $columns
        ));

        $ranked = DB::table($table)
            ->selectRaw("{$columnList}, ROW_NUMBER() OVER (PARTITION BY {$table}.booking_id ORDER BY {$table}.created_at DESC) as row_num")
            ->whereIn("{$table}.booking_id", $bookingIds);

        foreach ($filters as $column => $value) {
            $ranked->where("{$table}.{$column}", $value);
        }

        return DB::query()
            ->fromSub($ranked, 'ranked_rows')
            ->where('row_num', '<=', $perBooking)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, list<array{sent_at: ?string, template_name: string, sent_to_phone: string}>>
     */
    private function groupReminderRows(Collection $rows): array
    {
        $grouped = [];
        foreach ($rows as $event) {
            $bookingId = (int) $event->booking_id;
            $metadata = is_string($event->metadata) ? json_decode($event->metadata, true) : (array) ($event->metadata ?? []);
            $grouped[$bookingId][] = [
                'sent_at' => isset($event->created_at)
                    ? Carbon::parse($event->created_at)->toIso8601String()
                    : null,
                'template_name' => (string) data_get($metadata, 'template_name', '-'),
                'sent_to_phone' => (string) data_get($metadata, 'sent_to_phone', '-'),
            ];
        }

        return $grouped;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, list<array<string, mixed>>>
     */
    private function groupRescheduleRows(Collection $rows): array
    {
        $grouped = [];
        foreach ($rows as $item) {
            $bookingId = (int) $item->booking_id;
            $grouped[$bookingId][] = [
                'id' => (int) $item->id,
                'requested_by' => $item->requested_by,
                'request_source' => $item->request_source,
                'workflow_status' => $item->workflow_status,
                'old_tour_start_at' => $item->old_tour_start_at
                    ? Carbon::parse($item->old_tour_start_at)->toIso8601String() : null,
                'requested_tour_start_at' => $item->requested_tour_start_at
                    ? Carbon::parse($item->requested_tour_start_at)->toIso8601String() : null,
                'final_tour_start_at' => $item->final_tour_start_at
                    ? Carbon::parse($item->final_tour_start_at)->toIso8601String() : null,
                'requested_reason' => $item->requested_reason,
                'notes' => $item->notes,
                'reviewed_at' => $item->reviewed_at
                    ? Carbon::parse($item->reviewed_at)->toIso8601String() : null,
                'completed_at' => $item->completed_at
                    ? Carbon::parse($item->completed_at)->toIso8601String() : null,
                'created_at' => $item->created_at
                    ? Carbon::parse($item->created_at)->toIso8601String() : null,
            ];
        }

        return $grouped;
    }
}
