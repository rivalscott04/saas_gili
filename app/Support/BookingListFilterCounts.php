<?php

namespace App\Support;

final class BookingListFilterCounts
{
    /**
     * @param  iterable<int, object>  $bookings
     * @return array{
     *     status: array<string, int>,
     *     workflow: array<string, int>
     * }
     */
    public static function fromBookings(iterable $bookings): array
    {
        $statusCounts = [
            'all' => 0,
            'reschedule_requested' => 0,
            'confirmed' => 0,
            'on_tour' => 0,
            'standby' => 0,
            'pending' => 0,
            'cancelled' => 0,
        ];
        $workflowCounts = [
            'requested' => 0,
            'reviewed' => 0,
            'approved' => 0,
            'rejected' => 0,
            'completed' => 0,
            'no_request' => 0,
        ];

        foreach ($bookings as $booking) {
            $statusCounts['all']++;
            $status = strtolower((string) ($booking->status ?? ''));
            if ($status === 'on tour') {
                $statusCounts['on_tour']++;
            } elseif (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status]++;
            }

            if (strtolower((string) ($booking->customer_response ?? '')) === 'reschedule_requested') {
                $statusCounts['reschedule_requested']++;
            }

            $workflow = strtolower((string) ($booking->latestReschedule?->workflow_status ?? ''));
            if ($workflow === '' || $booking->latestReschedule?->workflow_status === null) {
                $workflowCounts['no_request']++;
            } elseif (array_key_exists($workflow, $workflowCounts)) {
                $workflowCounts[$workflow]++;
            }
        }

        return [
            'status' => $statusCounts,
            'workflow' => $workflowCounts,
        ];
    }
}
