<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GetYourGuide supplier API playground (UI tester)
    |--------------------------------------------------------------------------
    |
    | Sends server-side HTTP requests to a supplier base URL using Basic Auth,
    | matching the official Supplier-side OpenAPI paths. Enable explicitly in
    | non-local environments to avoid accidental exposure.
    |
    */

    'enabled' => (bool) env('GYG_SUPPLIER_PLAYGROUND_ENABLED', false),

    'http_timeout_seconds' => (int) env('GYG_SUPPLIER_PLAYGROUND_TIMEOUT', 45),

];
