<?php

return [
    /** Default date window for revenue recap when no filter is submitted (days). */
    'recap_default_days' => (int) env('BOOKINGS_RECAP_DEFAULT_DAYS', 90),

    /** Short cache for recap aggregates (seconds). */
    'recap_cache_seconds' => (int) env('BOOKINGS_RECAP_CACHE_SECONDS', 60),

    /** Max guides/tours loaded for superadmin manual booking pickers. */
    'manual_picker_limit' => (int) env('BOOKINGS_MANUAL_PICKER_LIMIT', 500),
];
