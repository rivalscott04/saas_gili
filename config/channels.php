<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-channel supplier identifiers
    |--------------------------------------------------------------------------
    |
    | Some partner APIs (e.g. GetYourGuide Supplier API) cap the supplier-side
    | booking reference length. We allocate a dedicated short reference per
    | booking so outbound sync and future channels stay consistent.
    |
    */
    'supplier_booking_reference' => [
        'max_length' => (int) env('SUPPLIER_BOOKING_REF_MAX_LENGTH', 25),
        'random_bytes' => (int) env('SUPPLIER_BOOKING_REF_RANDOM_BYTES', 8),
    ],

];
